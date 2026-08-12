<?php
declare(strict_types=1);

namespace NitsanAi\MyBlog\EventListener;

use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;

#[AsEventListener(identifier: 'myblog-email-header-modifier')]
class EmailHeaderModifier
{
    public function __invoke(BeforeMailerSentMessageEvent $event): void
    {
        $message = $event->getMessage();
        
        if ($message instanceof Email) {
            
            $currentSubject = $message->getSubject();
            
            if (str_contains((string)$currentSubject, 'Your Comment is Approved')) {
                return;
            }
            
            $message->subject('[URGENT] ' . $currentSubject);
            
            $message->getHeaders()->addTextHeader('X-MyBlog-System', 'Active');
        }
    }
}
