<?php

namespace App\Lib;

use Phalcon\Di\Injectable;
use Phalcon\Mvc\View\Simple;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends Injectable
{
    /**
     * @var PHPMailer
     */
    private $mailer;

    /**
     * @var \Phalcon\Mvc\View\Simple
     */
    protected $view;

    public function __construct($phpMailer)
    {
        $this->mailer = $phpMailer;
    }

    /**
     * Send welcome message to joined user
     *
     * @param Users $user
     * @return null
     */
    public function confirmEmail(string $email, string $username, string $token)
    {
        $params = [
            'confirmUrl' => '/users/email/confirm?token=' . $token,
            'username' => $username
        ];

        $html = $this->renderView('confirmEmail', $params);
        $txt = "Hi, $username!" . PHP_EOL . PHP_EOL;
        $txt .= 'Your account was created on EzVideo. To verify your e-mail address, click on the button bellow.' . PHP_EOL . PHP_EOL;
        $txt .= $this->config->application->publicUrl . $params['confirmUrl'] . PHP_EOL . PHP_EOL;
        $txt .= "If you didn't create a EzVideo account, let us know at support@ezvideo.net." . PHP_EOL;
        $txt .= 'Sincerely yours, EzVideo Team' . PHP_EOL . PHP_EOL;
        $txt .= 'Sent with love from EzVideo' . PHP_EOL;
        $txt .= 'Copyright ' . date('Y') . '. All rights reserved.';

        $this->mailer->setFrom($this->config->mail->noreplyEmail, $this->config->mail->noreplyName);
        $this->mailer->addAddress($email, $username);
        $this->mailer->isHTML();
        $this->mailer->Subject = 'Welcome to ezvideo!';
        $this->mailer->Body = $html;
        $this->mailer->AltBody = $txt;

        if ($this->mailer->send()) {
            return null;
        } else {
            return $this->mailer->isError();
        }
    }

    /**
     * Send submit contact form
     *
     * @param array $data
     * @return null
     */

    public function contactForm(array $data)
    {
        $html = $this->renderView('contactForm', $data);

        $txt = "Contact form inquiry" . PHP_EOL . PHP_EOL;
        $txt .= "First Name: " . $data['firstName'] . PHP_EOL . PHP_EOL;
        $txt .= "Last Name: " . $data['lastName'] . PHP_EOL . PHP_EOL;
        $txt .= "Email: " . $data['email'] . PHP_EOL . PHP_EOL;
        $txt .= "Inquiry: " . PHP_EOL;
        $txt .= $data['inquiry'] . PHP_EOL . PHP_EOL;
        $txt .= 'Sincerely yours, EzVideo Team' . PHP_EOL . PHP_EOL;
        $txt .= 'Sent with love from EzVideo' . PHP_EOL;
        $txt .= 'Copyright ' . date('Y') . '. All rights reserved.';

        $this->mailer->setFrom($this->config->mail->noreplyEmail, $this->config->mail->noreplyName);
        $this->mailer->addAddress($this->config->mail->supporter, "Support");
        $this->mailer->isHTML();
        $this->mailer->Subject = 'Contact form inquiry';
        $this->mailer->Body = $html;
        $this->mailer->AltBody = $txt;

        if ($this->mailer->send()) {
            return null;
        } else {
            return $this->mailer->isError();
        }
    }

    /**
     * Send Change password email
     *
     * @param array $data
     * @return null
     */

    public function changePassword(array $data)
    {
        $token = $data['token'];
        $username = $data['username'];
        $email = $data['email'];

        $params = [
            'confirmUrl' => '/users/password/forgot?token=' . $token,
            'username' => $username
        ];

        $html = $this->renderView('changePassword', $params);

        $txt = "Hi, $username !" . PHP_EOL . PHP_EOL;
        $txt .= "Someone has requested a link to change your password. You can do this through the button below." . PHP_EOL . PHP_EOL;
        $txt .= $this->config->application->publicUrl . $params['confirmUrl'] . PHP_EOL . PHP_EOL;
        $txt .= "If you didn't request this, please ignore this email. Your password won't change until you access
                 the link above and create a new one. " . $data['email'] . PHP_EOL . PHP_EOL;
        $txt .= 'Sincerely yours, EzVideo Team' . PHP_EOL . PHP_EOL;
        $txt .= 'Sent with love from EzVideo' . PHP_EOL;
        $txt .= 'Copyright ' . date('Y') . '. All rights reserved.';

        $this->mailer->setFrom($this->config->mail->noreplyEmail, $this->config->mail->noreplyName);
        $this->mailer->addAddress($email, $username);
        $this->mailer->isHTML();
        $this->mailer->Subject = 'Change your password';
        $this->mailer->Body = $html;
        $this->mailer->AltBody = $txt;

        if ($this->mailer->send()) {
            return null;
        } else {
            return $this->mailer->isError();
        }
    }

    /**
     * Send Change password email
     *
     * @param array $data
     * @return null
     */

    public function orderComplete(int $orderId, array $data)
    {
        $username = $data['username'];
        $email = $data['email'];
        $price = $data['price'];
        $placedOn = $data['placed_on'];
        $paymentAt = $data['payment_at'];

        $params = [
            'username' => $data['username'],
            'orderId' => $orderId,
            'price' => $data['price'],
            'placedOn' => $data['placed_on'],
            'paymentAt' => $data['payment_at']
        ];

        $html = $this->renderView('orderComplete', $params);
        $txt = "Hi, $username  !" . PHP_EOL . PHP_EOL;
        $txt .= "Thank you very much for your purchase !" . PHP_EOL . PHP_EOL;
        $txt .= "Order Id: $orderId	Price: $price	Placed On: $placedOn	Payment At: $paymentAt" . PHP_EOL . PHP_EOL;
        $txt .= 'Sincerely yours, EzVideo Team' . PHP_EOL . PHP_EOL;
        $txt .= 'Sent with love from EzVideo' . PHP_EOL;
        $txt .= 'Copyright ' . date('Y') . '. All rights reserved.';
        $this->mailer->setFrom($this->config->mail->noreplyEmail, $this->config->mail->noreplyName);
        $this->mailer->addAddress($email, $username);
        $this->mailer->isHTML();
        $this->mailer->Subject = 'Complete Order';
        $this->mailer->Body = $html;
        $this->mailer->AltBody = $txt;

        if ($this->mailer->send()) {
            return null;
        } else {
            return $this->mailer->isError();
        }
    }

    /**
     * Renders a view
     *
     * @param $viewPath
     * @param $params
     * @param null $viewsDir
     *
     * @return string
     */
    protected function renderView($viewPath, $params, $viewsDir = null) : string
    {
        $view = $this->getView();

        $params = array_merge([
            'publicUrl' => $this->config->application->publicUrl,
            'mediaUrl' => $this->config->application->mediaUrl
        ], $params);

        if ($viewsDir !== null) {
            $viewsDirOld = $view->getViewsDir();
            $view->setViewsDir($viewsDir);

            $content = $view->render($viewPath, $params);
            $view->setViewsDir($viewsDirOld);

            return $content;
        }

        return $view->render($viewPath, $params);
    }


    /**
     * Return a {@link \Phalcon\Mvc\View\Simple} instance
     *
     * @return \Phalcon\Mvc\View\Simple
     */
    protected function getView() : Simple
    {
        if (!$this->view) {
            /** @var $view \Phalcon\Mvc\View\Simple */
            $view = $this->getDI()->get('\Phalcon\Mvc\View\Simple');
            $view->setViewsDir($this->config->application->emailsDir);
            $view->registerEngines([
                ".phtml" => "Phalcon\Mvc\View\Engine\Php",
            ]);

            $this->view = $view;
        }

        return $this->view;
    }


}