<?php

namespace uhin\laravel_api\Helpers;

use Exception;
use SendGrid;
use SendGrid\Mail\Mail;

class SendGridTemplate
{

    /** @var Mail */
    private $email;

    /**
     * Create a new message instance.
     *
     * @param string $template
     * @throws SendGrid\Mail\TypeException
     */
    public function __construct(string $template)
    {
        $this->email = new Mail();
        $this->email->setFrom(config('mail.from.address'), config('mail.from.name'));
        $this->email->setReplyTo('noreply@uhin.org', config('mail.from.name'));
        $this->email->setTemplateId($template);
    }

    /**
     * Adds a recipient to the email. This is an individual recipient with
     * any additional information that you specify in the $personalization parameter,
     * ie: CCs, BCCs, etc.
     *
     * @param string $address
     * @param null|string $name
     * @param null|SendGrid\Mail\Personalization $personalization
     */
    public function addRecipient(string $address, ?string $name = null, ?SendGrid\Mail\Personalization $personalization = null)
    {
        if ($this->email->getPersonalizationCount() === 1 && $this->email->getPersonalizations()[0]->getTos() === null) {

            $this->email->addTo($address, $name);
            if ($ccs = $personalization->getCcs()) {
                $this->email->addCcs($ccs);
            }
            if ($bccs = $personalization->getBccs()) {
                $this->email->addBccs($bccs);
            }
            if ($subject = $personalization->getSubject()) {
                $this->email->setSubject($subject);
            }
            if ($headers = $personalization->getHeaders()) {
                $this->email->addHeaders($headers);
            }
            if ($data = $personalization->getDynamicTemplateData()) {
                $this->email->addDynamicTemplateDatas($data);
            }
            if ($args = $personalization->getCustomArgs()) {
                $this->email->addCustomArgs($args);
            }
            if ($sendAt = $personalization->getSendAt()) {
                $this->email->setSendAt($sendAt);
            }

        } else {
            $this->email->addTo($address, $name, null, null, $personalization);
        }
    }

    /**
     * Sends the message.
     *
     * @return void
     * @throws Exception
     */
    public function send()
    {
        $sendgrid = new SendGrid(config('mail.sendgrid.api-key'));
        $response = $sendgrid->send($this->email);
        if (($response->statusCode() < 200) || ($response->statusCode() > 206)) {
            throw new Exception('SendGrid Failure: Status ' . $response->statusCode() . ' - ' . $response->body());
        }
    }

}
