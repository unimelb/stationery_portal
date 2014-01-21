<?php
/**
 * Sample instance script for MailForm application.
 * 
 * Copy somewhere, and have it include the MailForm.class.php file. Edit your
 * instance script to include appropriate 'mailto' and 'smtp' parameters.
 *
 * You may set the value 'DEBUG' to a true value in order to get extra debugging
 * output.
 */

/**
 * Instance of MailForm
 */
require_once './MailForm.class.php';

/**
 * Application parameters
 */
$params = array(
    'mail_form_tmpl' => 'mail_form.html',
    'mail_tmpl'      => 'mail.html',
    // 'mailto'         => 'username@domain.tld',
    // 'smtp'           => '127.0.0.1',
    'DEBUG'          => false
);

/**
 * Instantiate MailForm
 */
$app = new MailForm(array(
    'TMPL_PATH'  => 'tmpl',
    'TMPL_ARGS'  => array('caching' => 0),
    'PARAMS'     => $params
));

/**
 * Run application
 */
$app->run();
?>
