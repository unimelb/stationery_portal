<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.errorHandling">
<refnamediv>
    <refname>Exception and Error Handling</refname>
    <refpurpose>
        Exception and PHP Error handling in Cgiapp2
    </refpurpose>
</refnamediv>

<refsect1 id="{@id}.errorHandling.overview">
    <title>Overview</title>
    <para>
        Cgiapp2 has several mechanisms for both triggering and handling errors
        and exceptions. These include:
    </para>
    <itemizedList>
        <listitem>{@link Cgiapp2::carp() carp()} - triggers PHP
            warnings</listitem>
        <listitem>{@link Cgiapp2::croak() croak()} - triggers PHP
            errors</listitem>
        <listitem>{@link Cgiapp2_Error} - observable PHP Error handler
            class</listitem>
        <listitem>{@link Cgiapp2_Exception} - observable Exception
            class</listitem>
        <listitem>{@link Cgiapp2_Exception_Error} - handles PHP errors by
            throwing them as {@link Cgiapp2_Exception}s.</listitem>
    </itemizedList>
</refsect1>

<refsect1 id="{@id}.errorHandling.carpCroak}">
    <title>Carp and Croak</title>
    <para>
        {@link Cgiapp2::carp() carp()} and {@link Cgiapp2::croak() croak()} take
        their names from the perl module {@link http://search.cpan.org/search?query=carp&mode=module Carp}.
        These methods are used to produce warnings and fatal errors,
        respectively; their usage in Cgiapp2 is to trigger PHP E_USER_WARNING and
        E_USER_ERROR errors.
    </para>
    <para>
        carp() and croak() are used within the Cgiapp2 class itself. carp() is
        used when a non-fatal condition occurs that the developer should
        probably correct. croak() is used when Cgiapp2 is missing information
        that is necessary for execution.
    </para>
    <para>
        Exceptions are not used because in the case of carp(), there's no need
        to halt current execution. croak() could probably trigger an exception
        instead of a PHP error, but doesn't for backwards compatibility
        purposes. This way, the developer doesn't need ot necessarily update all
        application instance scripts developed with prior Cgiapp2 versions
        (pre-2.0.0). Otherwise, they would need to add something like the
        following:
    </para>
    <programlisting role="php">
    <![CDATA[
<?php
require_once 'SomeApp.class.php';
try {
    $webapp = new SomeApp();
    $webapp->run();
} catch (Cgiapp2_Exception $e) {
    // do something with the exception...
}
?>
    ]]>
    </programlisting>
    <para>
        You can use carp() and croak() in your own development if you wish to
        utilize PHP's error handling mechanism. If you do, you might want to
        consider using {@link Cgiapp2_Error} as your error handler. See below for
        more details on the functionality it offers.
    </para>
</refsect1>

<refsect1 id="{@id}.errorHandling.cgiappError}">
    <title>Cgiapp2_Error Class</title>
    <para>
        {@link Cgiapp2_Error} is a class that can be used for handling PHP
        errors. To use it, simply add the following to your script or class:
    </para>
    <programlisting role="php">
    <![CDATA[
<?php
include_once 'Cgiapp2/Error.class.php';
set_error_handler(array('Cgiapp2_Error', 'handler'));
?>
    ]]>
    </programlisting>
    <para>
        Why use Cgiapp2_Error? Cgiapp2_Error is an observable class, which means
        you can create observer classes that will be notified when an error is
        raised. Such a class might send an email to you, log the error to a
        file, redirect to another page, etc.
    </para>
    <para>
        The Cgiapp2 distribution contains an interface class for creating
        observers, {@link Cgiapp2_Error_Observer_Interface}, and two sample
        observers:
    </para>
    <itemizedList>
        <listitem>{@link Cgiapp2_Error_Observer_Log}, for logging
            errors</listitem>
        <listitem>{@link Cgiapp2_Error_Observer_Mail}, for mailing
            errors</listitem>
    </itemizedList>
    <para>
        Observer classes basically need only {@link Cgiapp2_Error::attach() attach}
        themselves to the Cgiapp2_Error class, and then implement an event()
        method. For example:
    </para>
    <programlisting role="php">
    <![CDATA[
<?php
// Will register with Cgiapp2_Error
include_once 'Cgiapp2/Error.class.php';

// Implements Cgiapp2_Error_Observer_Interface
include_once 'Cgiapp2/Error/Observer/Interface.class.php';

class MyObserver implements Cgiapp2_Error_Observer_Interface
{
    public static function event(Cgiapp2_Error $e)
    {
        // Do something with the exception...
    }
}

// Register with Cgiapp2_Error
Cgiapp2_Error::attach(array('MyObserver'));
?>
    ]]>
    </programlisting>
</refsect1>

<refsect1 id="{@id}.errorHandling.cgiappException}">
    <title>Cgiapp2_Exception Class</title>
    <para>
        {@link Cgiapp2_Exception} is an exception class that can be used for
        throwing exceptions.  To use it, simply add the following to your script
        or class:
    </para>
    <programlisting role="php">
    <![CDATA[
<?php
include_once 'Cgiapp2/Exception.class.php';
?>
    ]]>
    </programlisting>
    <para>
        Why use Cgiapp2_Exception? Cgiapp2_Exception is an observable class, which
        means you can create observer classes that will be notified when an
        exception is thrown. Such a class might send an email to you, log the
        exception details to a file, redirect to another page, etc.
    </para>
    <para>
        The Cgiapp2 distribution contains an interface class for creating
        observers, {@link Cgiapp2_Exception_Observer_Interface}, and two sample
        observers:
    </para>
    <itemizedList>
        <listitem>{@link Cgiapp2_Exception_Observer_Log}, for logging
            errors</listitem>
        <listitem>{@link Cgiapp2_Exception_Observer_Mail}, for mailing
            errors</listitem>
    </itemizedList>
    <para>
        Observer classes basically need only {@link Cgiapp2_Exception::attach() attach}
        themselves to the Cgiapp2_Exception class, and then implement an event()
        method. For example:
    </para>
    <programlisting role="php">
    <![CDATA[
<?php
// Will register with Cgiapp2_Exception
include_once 'Cgiapp2/Exception.class.php';

// Implements Cgiapp2_Exception_Observer_Interface
include_once 'Cgiapp2/Exception/Observer/Interface.class.php';

class MyObserver implements Cgiapp2_Exception_Observer_Interface
{
    public static function event(Cgiapp2_Exception $e)
    {
        // Do something with the exception...
    }
}

// Register with Cgiapp2_Exception
Cgiapp2_Exception::attach(array('MyObserver'));
?>
    ]]>
    </programlisting>
</refsect1>

<refsect1 id="{@id}.errorHandling.cgiappExceptionError}">
    <title>Cgiapp2_Exception_Error Class</title>
    <para>
        {@link Cgiapp2_Exception_Error} inherits from {@link Cgiapp2_Exception},
        and is used to handle PHP errors by throwing them as Cgiapp2_Exceptions.
        To use it, simply add the following to your script or class:
    </para>
    <programlisting role="php">
    <![CDATA[
<?php
include_once 'Cgiapp2/Exception/Error.class.php';
set_error_handler(array('Cgiapp2_Exception_Error', 'handler'));
?>
    ]]>
    </programlisting>
    <para>
        Cgiapp2 uses Cgiapp2_Exception_Error internally during run mode execution,
        throwing a Cgiapp2_Exception_Error when encountering a PHP error. If such
        an exception is caught, it executes the 
        {@link Cgiapp2::error_mode() designated error mode}.
    </para>
    <para>
        Since Cgiapp2_Exception_Error inherits from Cgiapp2_Exception, any
        Cgiapp2_Exception observers you use will also handle any 
        Cgiapp2_Exception_Errors thrown. This is a powerful way to handle both
        exceptions and PHP errors.
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
