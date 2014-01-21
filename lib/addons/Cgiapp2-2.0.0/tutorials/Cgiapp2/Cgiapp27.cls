<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.templates">
<refnamediv>
    <refname>Template Engine Interface</refname>
    <refpurpose>
        How to use template engines with Cgiapp2.
    </refpurpose>
</refnamediv>

<refsect1 id="{@id}.templates.methods">
    <title>Cgiapp2 Template Methods</title>
    <para>
        Cgiapp2 utilizes template engines via the Callback Hook System. There
        are three hooks for templates: 
    </para>
    <itemizedlist>
        <listitem>tmpl_path: Initialize a template engine and/or re/set the
        path to templates.</listitem>
        <listitem>tmpl_assign: Assign variables to the template
        engine.</listitem>
        <listitem>tmpl_fetch: Fetch the contents of a template, i.e., compile
        the template and return the contents.</listitem>
    </itemizedlist>
    <para>
        If the template plugin you use implements each of these hooks, you may
        call all of these easily from within your code with these Cgiapp2
        methods:
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::tmpl_path() tmpl_path()}; most times, you
        will never need to call this, as it is called during application
        initialization. However, if at any point you want to change the path
        to templates from your application or another class, you would use
        this method.</listitem>
        <listitem>{@link Cgiapp2::tmpl_assign()}</listitem>
        <listitem>{@link Cgiapp2::load_tmpl()}</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.templates.plugins">
    <title>Template Plugin Classes</title>
    <para>
        Cgiapp2 ships with several template plugin classes:
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp_Plugin_Smarty}: Smarty</listitem>
        <listitem>{@link Cgiapp_Plugin_Savant2}: Savant2</listitem>
        <listitem>{@link Cgiapp_Plugin_Savant3}: Savant3</listitem>
    </itemizedlist>
    <para>
        If you wish to utilize another template engine, while still keeping
        the template agnostic interface Cgiapp2 provides, you can develop your
        own template plugin. To do so, implement the {@link
        Cgiapp2_Plugin_Template_Interface} class. In the class file, add the
        class to Cgiapp2's template hooks. As an example:
    </para>
    <programlisting role="php"><![CDATA[<?php
include_once 'Cgiapp2/Plugin/Template/Interface';

class MyTemplateEngine implements Cgiapp2_Plugin_Template_Interface
{
    public static function init(Cgiapp2 $cgiapp, $tmpl_path)
    {
        // Initialize template engine and/or change path to templates
    }

    public static function assign(Cgiapp2 $cgiapp)
    {
        // Assign variables to template
    }

    public static function fetch(Cgiapp2 $cgiapp, $tmpl_file)
    {
        // Fetch a compiled template...
    }
}

Cgiapp2::add_callback('tmpl_path', array('MyTemplateEngine', 'init'));
Cgiapp2::add_callback('tmpl_assign', array('MyTemplateEngine', 'init'));
Cgiapp2::add_callback('tmpl_fetch', array('MyTemplateEngine', 'init'));
?>]]></programlisting>
    <para>
        There are two efficient ways to implement the template engine:
    </para>
    <itemizedlist>
        <listitem>Store an instance of the template engine as a Cgiapp2 param:
        <programlisting role="php"><![CDATA[<?php
class MyTemplateEngine
{
    public $path;

    public function __construct($tmpl_path)
    {
        $this->path = $tmpl_path;
    }
    
    public static function init(Cgiapp2 $cgiapp, $tmpl_path)
    {
        if (!$cgiapp->param('MyTemplateEngine_Instance')) {
            $tmpl_engine = new MyTemplateEngine($tmpl_path)
        } else {
            $tmpl_engine = $cgiapp->param('MyTemplateEngine_Instance');
            $tmpl_engine->path = $tmpl_path;
        }
    
        return true;
    }
}
?>]]></programlisting>
        </listitem>
        <listitem>Implement a Singleton pattern in the plugin:
        <programlisting role="php"><![CDATA[<?php
class MyTemplateEngine
{
    private static $_instance = false;

    public $path;
    
    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new MyTemplateEngine();
        }
    
        return self::$_instance;
    }
    
    public static function init(Cgiapp2 $cgiapp, $tmpl_path)
    {
        self::getInstance()->path = $tmpl_path;
    }
}
?>]]></programlisting>
        </listitem>
    </itemizedlist>
    <para>
        For examples, see the template engine plugins distributed with Cgiapp2
        as noted above.
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
