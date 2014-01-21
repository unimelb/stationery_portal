<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.callbacks">
<refnamediv>
    <refname>Callback Hook System</refname>
</refnamediv>

<refsect1 id="{@id}.callbacks.purpose">
    <title>Introduction and Purpose</title>
    <para>
        Version 2.0.0 of Cgiapp2 introduces the callback hook system. This was
        originally introduced to CGI::Application during the fall of 2004 to
        provide even more flexibility to the already flexible CGI::Application;
        standard plugins for handling things such as authorization, form
        validation, and other template engines can then be developed and plugged
        into an application at specific points of execution to speed development
        and also to prevent the need to rewrite superclasses.
    </para>
    <para>
        The callback hook system implements a classic observer pattern. The
        object class becomes the subject, and the 
        {@link Cgiapp2::call_hook() call_hook()} method is used to notify
        observers. The 'hook' argument to 
        {@link Cgiapp2::call_hook() call_hook()} indicates the event that has
        been triggered, which in turn selects which observers to notify. All
        observers are passed an object instance of the observable class in order
        to effect its modification.
    </para>
</refsect1>

<refsect1 id="{@id}.callbacks.hooks"}>
    <title>Callback Hooks</title>
    <para>
        There are several pre-installed {@link Cgiapp2::$_INSTALLED_HOOKS hooks}
        you may tie into. These include:
    </para>
    <itemizedList>
        <listitem><emphasis>init</emphasis> - called during object
            instantiation, just prior to execution of the {@link Cgiapp2::setup()
            setup()} method. In your application class, 
            {@link Cgiapp2::cgiapp_init() cgiapp_init()} is automatically linked
            to this hook (if defined).</listitem>
        <listitem><emphasis>prerun</emphasis> - called just prior to run mode
            execution, and can change the selected run mode. In your application
            class, {@link Cgiapp2::cgiapp_prerun() cgiapp_prerun()} is
            automatically linked to this hook (if defined).</listitem>
        <listitem><emphasis>postrun</emphasis> - called after completion of the
            run mode, and can alter the content generated. In your application
            class, {@link Cgiapp2::cgiapp_postrun() cgiapp_postrun()} is
            automatically linked to this hook.</listitem>
        <listitem><emphasis>teardown</emphasis> - called at object destruction.
            In your application class, {@link Cgiapp2::teardown() teardown()} is
            automatically linked to this hook.</listitem>
        <listitem><emphasis>tmpl_path</emphasis> - called when setting a new
            template path. In your application class, you can simply call 
            {@link Cgiapp2::tmpl_path() tmpl_path()}, assuming you are using a
            template plugin that implements the hook.</listitem>
        <listitem><emphasis>tmpl_assign</emphasis> - called when assigning
            variables to a template. In your application class, you can simply
            call {@link Cgiapp2::tmpl_assign() tmpl_assign()}, assuming you are
            using a template plugin that implements the hook.</listitem>
        <listitem><emphasis>tmpl_fetch</emphasis> - called when loading a
            template (typically, fetching template contents). In your
            application class, you may simply call
            {@link Cgiapp2::load_tmpl() load_tmpl()}, assuming you are using a
            tmeplate plugin that implements the hook.</listitem>
    </itemizedList>
    <para>
        You may also create your own hooks using 
        {@link Cgiapp2::new_hook() new_hook()}, allowing other classes the
        ability to customize your application classes.
    </para>
    <para>
        To make use of the callback system, you must register callbacks with it.
        You do so with the {@link Cgiapp2::add_callback() add_callback()} method,
        which allows you to register a callback with a hook; additionally, you
        can prioritize when it is executed by optionally providing a class with
        which to register it. Those hooks registered with the class of the
        current object instance are executed first, while those of the class'
        ancestors getting executed by order of ancestry.
    </para>
    <para>
        Template hooks (tmpl_path, tmpl_assign, and load_tmpl) are treated
        slightly differently. The author cannot think of a usage where having
        multiple template engines present would be a good idea. Additionally,
        load_tmpl should really only ever return a single string value. As such,
        the template hooks always register with the Cgiapp2 class, and each
        callback registered replaces any callbacks previously registered with
        that hook (i.e., only one callback is allowed per template hook).
    </para>
</refsect1>

<refsect1 id="{@id}.callbacks.API_Summary">
    <title>API Summary</title>
    <itemizedList>
        <listitem>Callback hooks receive at the minimum a single argument, as
            the first argument in the argument list: the object instance of the
            calling class (the observed object). (A small list of them have the
            object instance as the last argument; see API Details.)</listitem>
        <listitem>When multiple callbacks are called for a single hook, the
            return values are returned as an array, each item representing the
            return value of a single callback. Otherwise the return value of the
            sole callback will be returned.</listitem>
    </itemizedList>
</refsect1>

<refsect1 id="{@id}.callbacks.API_Details">
    <title>API Details</title>
    <para>
        All callback hooks are passed at the minimum a single argument, the
        object instance of the calling class (the observed object). Some hooks
        take additional arguments:
    </para>
    <itemizedList>
        <listitem><emphasis>init</emphasis> is passed all arguments that were
        passed to {@link Cgiapp2::__construct() the constructor}; these are a
        single array, and constitute the second argument to the hook.</listitem>
        <listitem><emphasis>prerun</emphasis> is passed the requested run mode
        as the second argument to the hook.</listitem>
        <listitem><emphasis>postrun</emphasis> is passed the content generated
        by the run mode as the second argument to the hook.</listitem>
        <listitem><emphasis>tmpl_path</emphasis> is passed the template path as
        the second argument, and any extra parameters for the template engine as
        an associative array in the third argument.</listitem>
        <listitem><emphasis>tmpl_assign</emphasis> may be passed a variety of
        arguments; all arguments are passed on to the callback hook.</listitem>
        <listitem><emphasis>load_tmpl</emphasis> is passed the name of the
        template file as its second argument.</listitem>
    </itemizedList>
    <para>
        Additionally, the object instance is typically passed as the first
        argument to a hook. However, there are some special cases where it is
        passed as the <emphasis>final</emphasis> argument:
    </para>
    <itemizedList>
        <listitem>init</listitem>
        <listitem>prerun</listitem>
        <listitem>postrun</listitem>
    </itemizedList>
    <para>
        Finally, all callbacks registered with 
        {@link Cgiapp2::add_callback() add_callback()} must be valid PHP
        callbacks.
    </para>
</refsect1>

<refsect1 id="{@id}.callbacks.examples">
    <title>Examples</title>
    <para>
        Below are several examples of callback hooks in use. You may also want
        to see {@link Cgiapp2_Plugin_Smarty} for an example of a class that
        implements several callback hooks (the template callback hooks).
    </para>
</refsect1>

<refsect1 id="{@id}.callbacks.example1">
    <title>Example 1: Register a Callback with a Hook</title>
    <para>
        An example of how to register a callback:
    </para>
    <programlisting role="php">
    <![CDATA[
class SomeClass
{
    public static function prerunHook($rm, $cgiapp)
    {
        // Don't allow access to admin mode unless user is admin
        if (('admin' != $_SESSION['username'])
            && ('admin' == $rm))
        {
            $cgiapp->prerun_mode('login');
        }
    }
}
Cgiapp2::add_callback('prerun', array('SomeClass', 'prerunHook'), 'MyCgiapp2');
    ]]>
    </programlisting>
    <para>
        The above code creates a class, 'SomeClass', that registers a prerun
        hook with the class 'MyCgiapp2'. If certain conditions are met, it
        changes the run mode of MyCgiapp2 (or one of its descendents) to 'login'.
    </para>
</refsect1>

<refsect1 id="{@id}.callbacks.example2">
    <title>Example 2: Create and Implement a Custom Hook</title>
    <para>
        An example of how to create a new hook and register a callback with it:
    </para>
    <programlisting role="php">
    <![CDATA[
class SomeClass extends Cgiapp2
{
    protected function cgiapp_init($args, $cgiapp)
    {
        self::new_hook('postTemplate', 'SomeClass');
    }

    // some other stuff in between: setup(), run modes, etc.

    protected function cgiapp_postrun($body, $cgiapp)
    {
        $this->tmpl_assign('content', $body);
        $body = $this->load_tmpl($this->param('site_tmpl'));
        $body = $this->call_hook('postTemplate', $body);
        $cgiapp->postrun_body($body);
    }
}

class postClass
{
    function tidy($cgiapp, $body)
    {
        $tidy = new tidy;
        $tidy->parseString($body);
        $tidy->cleanRepair();

        return $body;
    }
}
Cgiapp2::add_callback('postTemplate', array('postClass', 'tidy'), 'SomeClass');
    ]]>
    </programlisting>
    <para>
        The above code creates a class, 'SomeClass', that creates a
        'postTemplate' hook; this hook is then called in cgiapp_postrun() after
        assigning the run mode content to a sitewide template.
    </para>
    <para>
        A new class, postClass, implements a method 'tidy' that takes some text
        and runs it through tidy. It is then added as a callback to the
        'postTemplate' hook.
    </para>
    <para>
        In effect, 'SomeClass' has just added a hook for manipulating the final
        content of the application.
    </para>
</refsect1>

<refsect1 id="{@id}.callbacks.plugins">
    <title>Included Plugins</title>
    <para>
        The Cgiapp2 distribution package includes a number of plugins ready for
        use:
    </para>
    <itemizedList>
        <listitem>{@link Cgiapp2_Plugin_Template_Interface}: an interface class
            for implementing template plugins. Plugins that conform to the
            interface can be used transparently via Cgiapp2's
            {@link Cgiapp2::tmpl_path() tmpl_path()}, 
            {@link Cgiapp2::tmpl_assign() tmpl_assign()}, and
            {@link Cgiapp2::load_tmpl() load_tmpl()} methods. This means that you
            can swap in any template plugin implementing the interface
            transparently to your application.</listitem>
        <listitem>{@link Cgiapp2_Plugin_Smarty}: a template plugin implementing
            {@link Cgiapp2_Plugin_Template_interface} to provide the Smarty
            template engine.</listitem>
        <listitem>{@link Cgiapp2_Plugin_Savant2}: a template plugin implementing
            {@link Cgiapp2_Plugin_Template_interface} to provide the Savant2
            template engine.</listitem>
        <listitem>{@link Cgiapp2_Plugin_Savant3}: a template plugin implementing
            {@link Cgiapp2_Plugin_Template_interface} to provide the Savant3
            template engine.</listitem>
    </itemizedList>
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
