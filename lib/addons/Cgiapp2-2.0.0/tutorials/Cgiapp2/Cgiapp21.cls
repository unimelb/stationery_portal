<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.example">
<refnamediv>
    <refname>Usage Example</refname>
</refnamediv>

<refsect1 id="{@id}.1.desc">
    <title>A simple form -&gt; list -&gt; show detail application</title>
    <para>
        Imagine you have to write an application to search through a database of
        widgets.  Your application has three screens:
    </para>
    <itemizedlist>
        <listitem><simpara>Search form</simpara></listitem>
        <listitem><simpara>List of results</simpara></listitem>
        <listitem><simpara>Detail of a single record</simpara></listitem>
    </itemizedlist>
    <para>
        To write this application using Cgiapp2 you will create two files:
    </para>
    <itemizedlist>
        <listitem><simpara>WidgetView.class.php -- Your "Application Class"</simpara></listitem>
        <listitem><simpara>widgetview.php -- Your "Instance Script"</simpara></listitem>
    </itemizedlist>
    <para>
        The Application Class contains all the code specific to your
        application functionality, and it exists outside of your web server's
        document root, somewhere in the PHP include path.
    </para>
    <para>
        The Instance Script is what is actually called by your web server.  It
        is a very small, simple file which simply creates an instance of your
        application and calls an inherited method, {@link Cgiapp2::run() run()}.
        Following is the entirety of "widgetview.php":
    </para>

    <programlisting role="php">
    <![CDATA[
<?php
   require_once 'WidgetView.class.php';
   $webapp = new WidgetView();
   $webapp->run();
?>
    ]]>
    </programlisting>

    <para>
        As you can see, widgetview.php simply "uses" your Application Class
        (which implements a PHP class called "WidgetView").  Your Application
        Class, "WidgetView.class.php", is somewhat more lengthy:
    </para>

    <programlisting role="php">
    <![CDATA[
 <?php
   // Needed for our database connection
   require_once 'DB.php';
   class WidgetView extends Cgiapp2 
   {
       function setup() 
       {
           // Set the legal run modes => methods table
           $this->run_modes(array(
               'mode1' => 'showform',
               'mode2' => 'showlist',
               'mode3' => 'showdetail'
           ));

           // Set the default mode to 'mode1'
           $this->start_mode('mode1');

           // Use the handle_errors() method to handle run mode errors
           $this->error_mode('handle_errors');

           // Set the mode parameter to 'mode'
           $this->mode_param('mode');

           // Connect to DBI database
           $db =& DB::connect($this->param('dsn'));
           $this->db =& $db;
       }

       function teardown() 
       {
           // Disconnect when we're done
           $self->db->disconnect();
       }

       function showform() 
       {
           $output = $this->load_tmpl($this->param('form_tmpl'));
           $output  = '';
           $output .= "<html><head><title>Widget Search Form</title></head>\n";
           $output .= "<body><form action=\"" . $_SERVER['SCRIPT_NAME']
                   .  "\" method=\"post\">";
           $output .= "<input type=\"text\" name=\"widgetcode\">";
           $output .= "<input type=\"hidden\" name=\"rm\" value=\"mode2\">";
           $output .= "<input type=\"submit\" name=\"submit\" value=\"Submit\">";
           $output .= "</form></body></html>";

           return $output;
       }

       function showlist() 
       {
           $widgetcode = $_REQUEST['widgetcode'];

           $output  = '';
           $output .= "<html><head><title>List of Matching Widgets</title></head><body>\n";

           // Do a bunch of stuff to select "widgets" from a database which
           // match the user-supplied value of "widgetcode" which has been
           // supplied from the previous HTML form via a query object.
           //
           // Each row will contain a link to a "Widget Detail" which 
           // provides an anchor tag, as follows:
           //
           //   "widgetview.php?rm=mode3&widgetid=XXX"
           //
           //  ...Where "XXX" is a unique value referencing the ID of
           // the particular "widget" upon which the user has clicked.

           $output .= "</body></html>";

           return $output;
       }

       function showdetail()
       {
           $widgetid = $_REQUEST['widgetid'];

           $output  = '';
           $output .= "<html><head><title>Widget Detail</title></head><body>";

           // Do a bunch of things to select all the properties of  
           // the particular "widget" upon which the user has
           // clicked.  The key id value of this widget is provided 
           // via the "widgetid" property, accessed via the CGI.pm
           // query object.

           $output .= "</body></html>";

           return $output;
       }

       // Handle application errors during the run mode
       function handle_errors($errno, $errstr)
       {
           $msg = "<p>The following error occurred: $errstr</p>";
           $this->_ERROR_BODY = $msg;
       }
   }
?>
    ]]>
    </programlisting>

    <para>
        Cgiapp2 takes care of implementing the {@link Cgiapp2::run() run()}
        method.  Notice that at no point do you call echo() to send any output
        to the browser.  Instead, all output is returned as a scalar.
    </para>

    <para>
        Cgiapp2's most significant contribution is in managing the application
        state.  Notice that all which is needed to push the application forward
        is to set the value of a HTML form parameter 'rm' to the value of the
        "run mode" you wish to handle the form submission.  This is the key to
        Cgiapp2.
    </para>
</refsect1>
</refentry>
<!-- Keep this comment at the end of the file
Local variables:
mode: sgml
sgml-omittag:t
sgml-shorttag:t
sgml-minimize-attributes:nil
sgml-always-quote-attributes:t
sgml-indent-step:1
sgml-indent-data:t
sgml-parent-document:nil
sgml-default-dtd-file:"../../../../../manual.ced"
sgml-exposed-tags:nil
sgml-local-catalogs:nil
sgml-local-ecat-files:nil
End:
vim600: syn=xml fen fdm=syntax fdl=2 si
-->
