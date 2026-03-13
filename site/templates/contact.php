<?php namespace ProcessWire;

/**
 * Contact Template — contact.php
 *
 * Renders a contact form using FrontendForms module if installed,
 * otherwise falls back to a hand-coded form with CSRF protection
 * and wireMail() for sending.
 */

// Breadcrumbs
$hero = renderBreadcrumbs($page);

$success_message = '';
$use_frontend_forms = $modules->isInstalled('FrontendForms');

// -------------------------------------------------------------------
// Option A: FrontendForms module
// -------------------------------------------------------------------
if ($use_frontend_forms) {

    $form = new \FrontendForms\Form('contact-form');
    $form->setMaxAttempts(5);
    $form->setMinTime(3);
    $form->setMaxTime(600);

    // Name
    $name = new \FrontendForms\InputText('contact-name');
    $name->setLabel('Your name');
    $name->setRule('required');
    $form->add($name);

    // Email
    $email = new \FrontendForms\InputEmail('contact-email');
    $email->setLabel('Email address');
    $email->setRule('required');
    $email->setRule('email');
    $form->add($email);

    // Phone
    $phone = new \FrontendForms\InputTel('contact-phone');
    $phone->setLabel('Phone number');
    $form->add($phone);

    // Subject
    $subject = new \FrontendForms\InputText('contact-subject');
    $subject->setLabel('Subject');
    $subject->setRule('required');
    $form->add($subject);

    // Message
    $message = new \FrontendForms\Textarea('contact-message');
    $message->setLabel('Message');
    $message->setRule('required');
    $form->add($message);

    // Submit
    $submit = new \FrontendForms\Button('contact-submit');
    $submit->setAttribute('value', 'Send message');
    $form->add($submit);

    // Process valid submission
    if ($form->isValid()) {
        $m = wireMail();
        $m->to($page->email ?: $config->adminEmail);
        $m->from($config->adminEmail);
        $m->fromName($site_name);
        $m->replyTo($email->getValue(), $name->getValue());
        $m->subject('[' . $site_name . '] ' . $subject->getValue());
        $m->body(
            "Name: {$name->getValue()}\n" .
            "Email: {$email->getValue()}\n" .
            "Phone: {$phone->getValue()}\n\n" .
            $message->getValue()
        );
        $m->send();

        $success_message = '<div class="rounded-lg bg-green-50 border border-green-200 p-6 text-green-800 mb-8">'
            . '<p class="font-semibold">Thank you for your message.</p>'
            . '<p class="mt-1 text-sm">We\'ll be in touch as soon as possible.</p>'
            . '</div>';
    }

    $form_html = $form->render();

// -------------------------------------------------------------------
// Option B: Fallback manual form
// -------------------------------------------------------------------
} else {

    $errors = [];
    $form_data = [
        'name'    => '',
        'email'   => '',
        'phone'   => '',
        'subject' => '',
        'message' => '',
    ];

    if ($input->post->submit) {
        // Verify CSRF token
        if (!$session->CSRF->validate()) {
            $errors[] = 'Invalid form submission. Please try again.';
        }

        $form_data['name']    = $sanitizer->text($input->post->name);
        $form_data['email']   = $sanitizer->email($input->post->email);
        $form_data['phone']   = $sanitizer->text($input->post->phone);
        $form_data['subject'] = $sanitizer->text($input->post->subject);
        $form_data['message'] = $sanitizer->textarea($input->post->message);

        if (empty($form_data['name']))    $errors[] = 'Please enter your name.';
        if (empty($form_data['email']))   $errors[] = 'Please enter a valid email address.';
        if (empty($form_data['subject'])) $errors[] = 'Please enter a subject.';
        if (empty($form_data['message'])) $errors[] = 'Please enter your message.';

        if (empty($errors)) {
            $m = wireMail();
            $m->to($page->email ?: $config->adminEmail);
            $m->from($config->adminEmail);
            $m->fromName($site_name);
            $m->replyTo($form_data['email'], $form_data['name']);
            $m->subject('[' . $site_name . '] ' . $form_data['subject']);
            $m->body(
                "Name: {$form_data['name']}\n" .
                "Email: {$form_data['email']}\n" .
                "Phone: {$form_data['phone']}\n\n" .
                $form_data['message']
            );
            $m->send();

            $success_message = '<div class="rounded-lg bg-green-50 border border-green-200 p-6 text-green-800 mb-8">'
                . '<p class="font-semibold">Thank you for your message.</p>'
                . '<p class="mt-1 text-sm">We\'ll be in touch as soon as possible.</p>'
                . '</div>';

            // Reset form data after successful send
            $form_data = array_fill_keys(array_keys($form_data), '');
        }
    }

    // Build fallback form HTML
    ob_start(); ?>
    <?php if (!empty($errors)): ?>
        <div class="rounded-lg bg-red-50 border border-red-200 p-6 text-red-800 mb-8">
            <p class="font-semibold mb-2">Please correct the following:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= $err ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $page->url ?>" class="space-y-6">
        <?= $session->CSRF->renderInput() ?>

        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" required
                       value="<?= htmlspecialchars($form_data['name']) ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($form_data['email']) ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= htmlspecialchars($form_data['phone']) ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <input type="text" id="subject" name="subject" required
                       value="<?= htmlspecialchars($form_data['subject']) ?>"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
            <textarea id="message" name="message" rows="6" required
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?= htmlspecialchars($form_data['message']) ?></textarea>
        </div>

        <div>
            <button type="submit" name="submit" value="1"
                    class="inline-flex items-center px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium
                           hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                           transition-colors">
                Send message
            </button>
        </div>
    </form>
    <?php $form_html = ob_get_clean();
}

// Assemble the page content
ob_start(); ?>

<section class="py-12 lg:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= $page->title ?></h1>

        <?php if ($page->body): ?>
            <div class="prose prose-lg text-gray-600 mb-8">
                <?= $page->body ?>
            </div>
        <?php endif; ?>

        <?= $success_message ?>

        <?php if (!$success_message): ?>
            <?= $form_html ?>
        <?php endif; ?>

    </div>
</section>

<?php $content = ob_get_clean();
