<?php
/**
 * Cgiapp2 - Framework for building reusable web-applications
 *
 * A PHP5 port of perl's CGI::Application, a framework for building reusable web
 * applications. 
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>; based on
 * CGI::Application, by Jesse Erlbaum <jesse@erlbaum.net>, et. al.
 * @copyright (c) 2004 - present, Matthew Weier O'Phinney
 * @license BSD License (http://www.opensource.org/licenses/bsd-license.php)
 * @category Tools and Utilities
 * @tutorial Cgiapp2/Cgiapp2.cls
 * @version $Id:$
 */

/**
 * Extends Cgiapp2
 */
require_once 'Cgiapp2.class.php';

/**
 * Uses Smarty for templating
 */
require_once 'Cgiapp2/Plugin/Smarty.class.php';

/**
 * A simple mail form
 *
 * A class to implement a simple email contact form. This class utilizes
 * PEAR::Mail to send the actual email, and uses Smarty for the template engine
 * for generating the form and success pages. 
 *
 * Two parameters are required for instantiation:
 * <ul>
 *     <li><b>mailto</b>: the email address to whom submissions will be
 *     sent</li>
 *     <li><b>smtp</b>: the SMTP server via which the mail will be sent</li>
 * </ul>
 *
 * All other parameters are optional.
 *
 * See the accompanying mailform.php file for an example of an instance script.
 *
 * This class assumes that Cgiapp2.class.php is in your include path.
 *
 * @package Cgiapp2
 * @version @release-version@
 */
class MailForm extends Cgiapp2 
{
    /**
     * Name of template used by HTML_QuickForm to display form. Defaults to
     * 'mail_form.html'.
     * @var string 
     * @access protected
     */
    protected $MAIL_FORM_TMPL;

    /**
     * Template for displaying success message; defaults to 'mail.html'.
     * @var string 
     * @access protected
     */
    protected $MAIL_TMPL;

    /**
     * Email address of recipient.
     * @var string 
     * @access protected
     */
    protected $MAILTO;

    /**
     * Hostname or IP address of SMTP relay server.
     * @var string 
     * @access protected
     */
    protected $SMTP;

    /**
     * Setup application
     *
     * Sets up run modes, mode parameter, and start mode. Mode parameter is 'q'.
     *
     * Additionally, sets up default template filenames, and croaks if no
     * 'mailto' or 'smtp' parameters have been passed to the application.
     *
     * @access protected
     */
    public function setup() 
    {
        $this->mode_param('q');
        $this->start_mode('show');
        $this->run_modes(array(
            'show' => 'showForm',
            'mail' => 'mailSubmission'
        ));

        if (!$this->param('mail_form_tmpl')) {
            $this->param('mail_form_tmpl', 'mail_form.html');
        }
        if (!$this->param('mail_tmpl')) {
            $this->param('mail_tmpl', 'mail.html');
        }

        if (!$this->param('mailto')) {
            $this->croak('No recipient specified');
        }
        if (!$this->param('smtp')) {
            $this->croak('No SMTP server specified');
        }
    }

    /**
     * Postrun actions
     *
     * If the {@link $DEBUG} property is set, appends debug information to the
     * content.
     *
     * @return string
     * @access protected
     */
    protected function cgiapp_postrun(&$body) 
    {
        $q =& $this->query();
        if ($this->param('debug')) {
            $body .= "<h4>Request:</h4>";
            $body .= "<pre>" . print_r($q, 1) . "</pre>";
        }
    }

    /**
     * Display contact form
     *
     * @return string
     * @access protected
     */
    protected function showForm() 
    {
        return $this->load_tmpl($this->param('mail_form_tmpl'));
    }

    /**
     * Mail from form
     *
     * Validate the form and send an email.
     *
     * @access protected
     * @return string
     */
    protected function mailSubmission() 
    {
        // Validate...
        list($values, $errors) = $this->validate();
        if (!empty($errors)) {
            $this->tmpl_assign('errors', $errors);
            return $this->showForm();
        }

        // Send mail
        $this->sendMail($values);

        $this->tmpl_assign('values', $values);
        return $this->load_tmpl($this->param('mail_tmpl'));
    }

    /**
     * Validate form
     *
     * Attempts to validate the form. An array is returned with two elements.
     * The first is an array of values found (key => value); the second is an
     * associative array of keys to error messages.
     * 
     * @access protected
     * @return array Array with two arrays.
     */
    protected function validate()
    {
        $values = array(
            'fromName'  => '',
            'fromEmail' => '',
            'subject'   => '',
            'message'   => ''
        );

        $errors = array();

        // From name
        if (!empty($_POST['fromName'])
            && preg_match('/^[a-z\',. -]+$/i', $_POST['fromName']))
        {
            $values['fromName'] = htmlentities(trim($_POST['fromName']));
        } else {
            if (!empty($_POST['fromName'])) {
                $values['fromName'] = htmlentities(trim($_POST['fromName']));
            }
            $errors['fromName'] = 'Please enter a name consisting of letters, spaces, and minor punctuation only';
        }

        // From email. Regex from Solar project
        $emailRegex = '/^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))@((\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9\-])+\.)+[A-Za-z\-]+))$/';
        if (!empty($_POST['fromEmail'])
            && preg_match($emailRegex, $_POST['fromEmail']))
        {
            $values['fromName'] = htmlentities(trim($_POST['fromEmail']));
        } else {
            if (!empty($_POST['fromEmail'])) {
                $values['fromEmail'] = htmlentities(trim($_POST['fromEmail']));
            }
            $errors['fromEmail'] = 'Please provide a valid email address.';
        }

        // Subject
        if (!empty($_POST['subject'])
            && preg_match('/^[a-z0-9\'\",.)(+\/$#@!&* -]+$/i', $_POST['subject']))
        {
            $values['subject'] = htmlentities(trim($_POST['subject']));
        } else {
            if (!empty($_POST['subject'])) {
                $values['subject'] = htmlentities(trim($_POST['subject']));
            }
            $errors['subject'] = 'Please provide a subject line.';
        }

        // Message body
        if (!empty($_POST['message']))
        {
            $values['message'] = htmlentities(trim($_POST['message']));
        } else {
            $errors['message'] = 'Please provide a subject line.';
        }

        return array($values, $errors);
    }

    /**
     * Send an email
     *
     * Returns false on error; otherwise, true.
     *
     * @access public
     * @param array $values
     * @return bool
     */
    public function sendMail($values) 
    {
        $to      = $this->param('mailto');
        $smtp    = $this->param('smtp');
        $subject = $values['subject'];
        $body    = $values['message'];
        $from    = $values['fromEmail'];
        if (!empty($values['fromName'])) {
            $fullName = $values['fromName'] . " <$from>";
        } else {
            $fullName = $from;
        }

        $M_Params = array(
            'To'      => $to,
            'From'    => $fullName,
            'Subject' => $subject
        );

        include_once 'Mail.php';
        PEAR::setErrorHandling(PEAR_ERROR_RETURN);
        $mail = Mail::factory('smtp', array('host' => $smtp, 'port' => '25'));
        if (PEAR::isError($mail)) return false;
        $sent = $mail->send($to, $M_Params, wordwrap($body, 72));
        if (PEAR::isError($sent)) {
            return $sent;
        }

        return true;
    }
}
?>
