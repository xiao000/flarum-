<?php
namespace Flagrow\UsersList\Api\Controller;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\UserRepository;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Diactoros\Response\EmptyResponse;

class SendAdminEmailController implements RequestHandlerInterface
{
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param Mailer $mailer
     * @param TranslatorInterface $translator
     * @param UserRepository $users
     */
    public function __construct(SettingsRepositoryInterface $settings, Mailer $mailer, TranslatorInterface $translator, UserRepository $users)
    {
        $this->settings = $settings;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $actor = $request->getAttribute('actor');

        if ($actor !== null && $actor->isAdmin()) {
            $data = Arr::get($request->getParsedBody(), 'data', []);

            if ($data['forAll']) {
                $this->users->query()->chunk(50, function($users) use ($data) {
                    foreach ($users as $user) {
                        $this->sendMail($user->email, $data['subject'], $data['text']);
                    }
                });
            } else {
                foreach ($data['emails'] as $email) {
                    $this->sendMail($email, $data['subject'], $data['text']);
                }
            }
        }

        return new EmptyResponse;
    }

    protected function sendMail($email, $subject, $text)
    {
        $this->mailer->send(['raw' => $text], [], function (Message $message) use ($email, $subject) {
            $message->to($email);
            $message->subject('[' . $this->settings->get('forum_title') . '] ' . ($subject !== '' ? $subject : $this->translator->trans('veroz1-users-list.email.default_subject')));
        });
    }
}
