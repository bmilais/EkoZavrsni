<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
  /**
   * Šalje magic-link email učeniku za prijavu.
   * Vraća true u slučaju uspjeha, inače poruku greške.
   */
  public static function posaljiLinkUceniku(string $email, string $ime, string $prezime, string $hash): ?string
  {
    $link = rtrim((string)getenv('APP_URL') ?: '', '/') . '/#/prijava?token=' . rawurlencode($hash);

    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host       = (string)getenv('MAIL_HOST');
      $mail->Port       = (int)(getenv('MAIL_PORT') ?: 2525);
      $mail->SMTPAuth   = (string)getenv('MAIL_USERNAME') !== '';
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
      $mail->addAddress($email, $prezime . ' ' . $ime);

      $mail->Subject = 'Prijava u sustav odabira završnih radova';
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

      if ((int)getenv('MAIL_DEBUG') === 1) {
        $mail->SMTPDebug = 2;
      }

      $mail->send();
      return null;
    } catch (\Throwable $e) {
      error_log('Mail greška: ' . $e->getMessage());
      return 'Ne mogu poslati email: ' . $e->getMessage();
    }
  }
}
