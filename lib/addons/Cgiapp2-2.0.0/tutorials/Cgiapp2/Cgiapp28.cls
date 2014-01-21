<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.migration">
<refnamediv>
    <refname>Migrating from Cgiapp to Cgiapp2</refname>
    <refpurpose>
        Checklist of steps to take when migrating Cgiapp &lt; 2.0.0
        applications to Cgiapp2.
    </refpurpose>
</refnamediv>

<refsect1 id="{@id}.migration.checklist">
    <title>Checklist</title>
    <orderedlist>
        <listitem>Change all references of 'Cgiapp' to 'Cgiapp2'</listitem>
        <listitem>Make sure that any class inheriting directly from Cgiapp2
        implements the {@link Cgiapp2::setup() setup()} method. This is
        necessary because this method is marked as abstract.</listitem>
        <listitem>
            Choose a template plugin (or write your own). Include it in any of:
            <itemizedlist>
                <listitem>Superclass</listitem>
                <listitem>Application class(es)</listitem>
                <listitem>Instance script(s)</listitem>
            </itemizedlist>
            A typical include would look like:
            <programlisting role="php"><![CDATA[<?php
include_once 'Cgiapp2/Plugin/Smarty.class.php';
?>]]></programlisting>
        </listitem>
        <listitem>
            If using {@link Cgiapp2::cgiapp_postrun()} or a postrun 
            {@link Cgiapp2::call_hook() callback hook}:
            <itemizedList>
                <listitem>$body is no longer passed by reference</listitem>
                <listitem>If changing or replacing the value of $body, use
                {@link Cgiapp2::postrun_body()} to do so.</listitem>
            </itemizedList>
        </listitem>
    </orderedlist>
    <para>
        Additionally, a number of methods were made static, and you will need
        to make sure any calls to these methods are made statically:
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::array_to_hash() array_to_hash}</listitem>
        <listitem>{@link Cgiapp2::cap_hash() cap_hash}</listitem>
        <listitem>{@link Cgiapp2::carp() carp}</listitem>
        <listitem>{@link Cgiapp2::croak() croak}</listitem>
        <listitem>{@link Cgiapp2::is_assoc_array() is_assoc_array}</listitem>
        <listitem>{@link Cgiapp2::query() query}</listitem>
    </itemizedlist>
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
