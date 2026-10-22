<?php

return [
    'mail' => [
        'greeting' => 'Hi :name,',
        'signature' => 'Best regards,'."\n".'The :company team',
        'footer' => 'This is your private link: do not share it. If you have questions, reply to this email or message us on WhatsApp.',
        'button_portal' => 'Open my order',

        'contract_issued_subject' => 'Your service contract for the :vehicle',
        'contract_issued_heading' => 'Your contract is ready to sign',
        'contract_issued_line1' => 'We have prepared the service contract for your order :reference (:vehicle).',
        'contract_issued_line2' => 'You can read and sign it on your phone in 2 minutes. When signing we will email you a code to confirm it is you.',
        'contract_issued_line3' => 'The signing link is valid until :date.',
        'contract_issued_button' => 'Review and sign',

        'contract_reminder_subject' => 'Reminder: your contract is pending signature',
        'contract_reminder_heading' => 'Your contract is still pending',
        'contract_reminder_line1' => 'You have not signed the contract for your order :reference (:vehicle) yet. It expires on :date.',
        'contract_reminder_line2' => 'If you have any doubts, press «I have questions» on the contract page and we will call you.',

        'contract_signed_subject' => 'Contract signed — order :reference',
        'contract_signed_heading' => 'Contract signed!',
        'contract_signed_line1' => 'We have received your signature of the service contract for the :vehicle. A copy is attached.',
        'contract_signed_line2' => 'Next step: pay the deposit (:amount €). Payment details are on your page.',
        'contract_signed_line2_nofee' => 'We are already on the next step: inspecting the car.',
        'contract_signed_line3' => 'Fingerprint of the signed document (SHA-256): :hash',

        'phase_changed_subject' => 'News about your :vehicle: :state',
        'phase_changed_heading' => ':state',
        'phase_changed_line1' => 'Your order :reference has moved to a new phase.',

        'payment_subject' => 'Payment received — order :reference',
        'payment_heading' => 'We have received your payment',
        'payment_line1' => 'We confirm the payment of :amount € (:concept) by :method.',
        'payment_line2' => 'Thank you. You can see the status of all your payments on your page.',

        'approval_subject' => 'We need your answer — :title',
        'approval_heading' => 'We need your OK',
        'approval_line1' => ':title',
        'approval_amount' => 'Amount: :amount €',
        'approval_line2' => 'We explain it with photos on your page. You can answer with one click before :date.',
        'approval_button' => 'Review and answer',

        'message_subject' => 'New message about your :vehicle',
        'message_heading' => 'We have written to you',
        'message_button' => 'View and reply',

        'otp_subject' => 'Your verification code: :code',
        'otp_heading' => 'Your verification code',
        'otp_line1' => 'Use this code to :purpose on your order :reference:',
        'otp_line2' => 'It expires in :minutes minutes. If you did not request it, ignore this email.',
        'otp_purpose_sign_contract' => 'sign the contract',
        'otp_purpose_approve' => 'confirm your answer',
        'otp_purpose_cancel' => 'confirm the cancellation',

        'recover_subject' => 'Your :company tracking link',
        'recover_heading' => 'Here is your link',
        'recover_line1' => 'You asked to recover access to your order :reference (:vehicle).',

        'team_contract_signed_subject' => '✍️ :client has signed the contract (:reference)',
        'team_contract_signed_line' => ':client has signed the contract for the :vehicle. Fee payments have been created.',
        'team_contract_rejected_subject' => '⚠️ :client has questions about the contract (:reference)',
        'team_contract_rejected_line' => 'Reason given by the client: :reason',
        'team_contract_voided_subject' => 'Contract voided — :reference',
        'team_contract_voided_line' => 'The contract of :client (:vehicle) has been voided: :reason. Reissue it from the order.',
        'team_approval_resolved_subject' => ':client has answered: :title (:reference)',
        'team_approval_expired_subject' => 'No answer: :title (:reference)',
        'team_message_subject' => '💬 New message from :client (:reference)',
        'team_cancellation_subject' => '🛑 :client asks to cancel order :reference',
        'team_cancellation_line' => 'Reason: :reason. Review the order and contact the client.',
        'team_button' => 'Open order',
    ],
];
