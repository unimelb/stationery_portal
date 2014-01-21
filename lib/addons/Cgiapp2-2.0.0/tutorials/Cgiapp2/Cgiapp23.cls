<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.description">
<refnamediv>
    <refname>Purpose and Conventions</refname>
</refnamediv>

<refsect1 id="{@id}.description.purpose">
    <title>Purpose</title>
    <para>
        Cgiapp2 is a PHP5 abstract class; it is not intended that this class be
        instantiated directly. Instead, it is intended that your application
        will be implemented as a <emphasis>subclass</emphasis> (aka extension)
        of Cgiapp2.
    </para>

    <para>
        To inherit from Cgiapp2, simply create a new class that extends it:
    </para>

    <programlisting role="php">
    <![CDATA[
require_once 'Cgiapp2.class.php';
class WebApp extends Cgiapp2 { ... }
    ]]>
    </programlisting>
</refsect1>

<refsect1 id="{@id}.description.notation">
    <title>Notation and Conventions</title>
    <para>
        For the purpose of this document, we will refer to the following
        conventions:
    </para>
    <itemizedlist>
        <listitem><emphasis>Application Class:</emphasis> A Cgiapp2
            subclass/extension.</listitem>
        <listitem><emphasis>Instance Script:</emphasis> A PHP script that
            creates an object instance of the Application Class.</listitem>
        <listitem><emphasis>WebApp.class.php:</emphasis> The PHP file which
            implements your Application Class.</listitem>
        <listitem><emphasis>WebApp:</emphasis> The actual Application
            Class.</listitem>
        <listitem><emphasis>webapp.php:</emphasis> The Instance Script which
            implements your Application Class.</listitem>
        <listitem><emphasis>$webapp:</emphasis> An instance (object) of your
            Application Class.</listitem>
        <listitem><emphasis>$this:</emphasis> Same as $webapp, used in instance
            methods to pass around the current object. (Standard PHP
            Object-Oriented syntax)</listitem>
    </itemizedlist>
</refsect1>
<refsect1 id="{@id}.description.runmodes">
    <title>IMPORTANT NOTE ABOUT RUN MODE METHODS</title>
    <para>
        In the perl class from which this is derived, you would never send
        output to the screen from your application; you would leave this up to
        the {@link Cgiapp2::run() run()} method, which would load your
        template(s) and send the entire page at once. Part of the reason for
        this is that in perl, you need to send your HTTP headers manually each
        time, and the {@link Cgiapp2::run() run()} method takes care of that for
        you.
    </para>
    <para>
        This is <emphasis>not</emphasis> the case with PHP, though I would
        advise to try to follow this methodology as closely as possible.
        Breaking this rule can be a source of errors, particularly if you are
        trying to set any non-standard HTTP headers using Cgiapp2's various
        header methods, or if you ar setting cookies.
    </para>
    <para>
        One reason for using this class is because you <emphasis>want</emphasis>
        to separate program logic from design logic. This may be especially
        important if the design team works separately from the programming team,
        or if you are trying to make applications portable. For these reasons
        alone, you should attempt to only create output using the run method,
        which will fill templates according to the run mode called.
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
