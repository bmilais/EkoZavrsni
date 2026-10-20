<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Slanje magic-link mailova učenicima.
 *
 * Pojedinačno:  MailService::posaljiLinkUceniku(...)
 * Skupno:       $mail = MailService::otvoriVezu();
 *               foreach (...) { MailService::posalji($mail, ...); }
 *               MailService::zatvoriVezu($mail);
 *
 * Kod skupnog slanja cijela petlja koristi JEDNU SMTP vezu (SMTPKeepAlive),
 * umjesto da se za svakog učenika iznova spaja, radi TLS i prijavu.
 */
final class MailService
{
  private const NASLOV = 'Prijava u sustav odabira završnih radova';

  /**
   * Šalje magic-link email jednom učeniku.
   * Vraća null u slučaju uspjeha, inače poruku greške.
   */
  public static function posaljiLinkUceniku(string $email, string $ime, string $prezime, string $hash): ?string
  {
    try {
      $mail = self::otvoriVezu();
    } catch (\Throwable $e) {
      error_log('Mail greška: ' . $e->getMessage());
      return 'Ne mogu poslati email: ' . $e->getMessage();
    }

    try {
      return self::posalji($mail, $email, $ime, $prezime, $hash);
    } finally {
      self::zatvoriVezu($mail);
    }
  }

  /**
   * Priprema PHPMailer s postavkama iz .env. Veza prema SMTP serveru otvara
   * se pri prvom send() i ostaje otvorena (SMTPKeepAlive) do zatvoriVezu().
   */
  public static function otvoriVezu(): PHPMailer
  {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host          = (string)getenv('MAIL_HOST');
    $mail->Port          = (int)(getenv('MAIL_PORT') ?: 2525);
    $mail->SMTPKeepAlive = true;
    // PHPMailer bi inače čekao do 300 s na server koji ne odgovara.
    $mail->Timeout       = 20;

    $mail->SMTPAuth = (string)getenv('MAIL_USERNAME') !== '';
    if ($mail->SMTPAuth) {
      $mail->Username = (string)getenv('MAIL_USERNAME');
      $mail->Password = (string)getenv('MAIL_PASSWORD');
    }

    $enc = (string)getenv('MAIL_ENCRYPTION');
    if ($enc === 'tls') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($enc === 'ssl') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom((string)getenv('MAIL_FROM') ?: 'eko@vub.hr', (string)getenv('MAIL_FROM_NAME'));

    if ((int)getenv('MAIL_DEBUG') === 1) {
      $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
      // Debug ide u PHP error log, ne u izlaz - inače pokvari JSON odgovor.
      $mail->Debugoutput = 'error_log';
    }

    return $mail;
  }

  /**
   * Šalje jedan mail preko već otvorene veze. Vraća null ili poruku greške.
   * Nakon greške resetira SMTP sesiju, pa petlja može nastaviti sa sljedećim
   * učenikom (isto kao PHPMailerov primjer za mailing liste).
   */
  public static function posalji(PHPMailer $mail, string $email, string $ime, string $prezime, string $hash): ?string
  {
    $link = rtrim((string)getenv('APP_URL') ?: '', '/') . '/#/prijava?token=' . rawurlencode($hash);

    try {
      $mail->clearAddresses();

      $mailTo = (string)getenv('MAIL_TO');
      if ($mailTo !== '') {
        $mail->addAddress($mailTo);
        $mail->Subject = '[TEST -Umjesto: ' . $email . '] ' . self::NASLOV;
      } else {
        $mail->addAddress($email, $prezime . ' ' . $ime);
        $mail->Subject = self::NASLOV;
      }

      $mail->Body    =
        '<p>Pozdrav ' . htmlspecialchars($ime, ENT_QUOTES, 'UTF-8') . ',</p>' .
        '<p>pristupite sustavu za odabir završnih radova klikom na sljedeći link:</p>' .
        '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Otvori sustav</a></p>' .
        '<p>Ako link ne radi, kopirajte ga u preglednik:<br>' .
        htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</p>';
      $mail->AltBody =
        "Pozdrav {$ime},\n" .
        "pristupite sustavu za odabir završnih radova na:\n" .
        $link;

      $mail->send();
      return null;
    } catch (\Throwable $e) {
      error_log('Mail greška (' . $email . '): ' . $e->getMessage());
      $smtp = $mail->getSMTPInstance();
      if ($smtp->connected()) {
        $smtp->reset();
      }
      return 'Ne mogu poslati email: ' . $e->getMessage();
    }
  }

  public static function zatvoriVezu(PHPMailer $mail): void
  {
    $mail->smtpClose();
  }
}
