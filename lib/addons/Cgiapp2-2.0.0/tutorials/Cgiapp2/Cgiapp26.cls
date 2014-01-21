<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.methods">
<refnamediv>
    <refname>Types of Methods</refname>
    <refpurpose>
        A breakdown of the types of methods and what they do.
    </refpurpose>
</refnamediv>

<refsect1 id="{@id}.methods.instance">
    <title>Instance Script Methods</title>
    <para>
        By inheriting from Cgiapp2, you have access to a number of built-in
        methods.  The only one which is expected to be called from
        your Instance Script is:
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::run() run()}</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.methods.override">
    <title>Subclassing and Override Methods</title>
    <para>
        Cgiapp2 implements some methods which are expected to be overridden by
        implementing them in your subclass.  These methods are as follows:
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::setup() setup()}. Since Cgiapp2 is an abstract
        class, and because this method is marked as abstract, this method
        <emphasis>must</emphasis> be overridden in your child class</listitem>
        <listitem>{@link Cgiapp2::teardown() teardown()}</listitem>
        <listitem>{@link Cgiapp2::cgiapp_init() cgiapp_init()}</listitem>
        <listitem>{@link Cgiapp2::cgiapp_prerun() cgiapp_prerun()}</listitem>
        <listitem>{@link Cgiapp2::cgiapp_postrun() cgiapp_postrun()}</listitem>
        <listitem>cgiapp_get_query() (<emphasis>removed</emphasis>): This method
            was in the original perl and was used as a wrapper for returning an
            object capable of returning CGI parameters; this is unnecessary in
            PHP (CGI parameters are available at all times in the $_REQUEST,
            $_POST, $_GET, and $_COOKIE arrays), and was thus
            removed.</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.methods.utility">
    <title>Application Utility Methods</title>
    <para>
        These methods are mainly for helping debug your Application Class and/or
        Instance Script.
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::dump() dump()}</listitem>
        <listitem>{@link Cgiapp2::dump_html() dump_html()}</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.methods.header">
    <title>Application HTTP Header Methods</title>
    <para>
        The following methods can be used to modify the HTTP headers returned by
        your application. They are available at any point in the Cgiapp2 process
        and are available to any Application subclass as well.
    </para>

    <para>
        <emphasis>IMPORTANT NOTE REGARDING HTTP HEADERS:</emphasis> It is
        through the {@link Cgiapp2::header_props() header_props()} method that
        you may modify the outgoing HTTP headers.  This is necessary when you
        want to set the mime type to something other than "text/html", or
        perform a redirect.  The {@link Cgiapp2::header_props() header_props()}
        method works in conjunction with the {@link Cgiapp2::header_type() header_type()} method.  The value contained in 
        {@link Cgiapp2::header_type() header_type()} determines what HTTP headers
        are passed; a 'redirect' sets the Location header, while all others are
        passed verbatim.  
    </para>

    <para>
        Understanding this relationship is important if you wish to manipulate
        the HTTP headers properly.
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::header_props() header_props()}</listitem>
        <listitem>{@link Cgiapp2::header_type() header_type()}</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.methods.template">
    <title>Application Template Methods</title>
    <para>
        Prior to the 2.0.0 release, Cgiapp2 used Smarty as the default template
        engine, and allowed overriding the template methods to implement other
        template engines. As of 2.0.0, these methods simply call plugin hooks.
        What this means is twofold: (1) if you want to use a template engine,
        you need to include a template plugin in either your instance script,
        application class, or superclass; (2) such a plugin, if it implements
        {@link Cgiapp2_Plugin_Template_Interface}, will allow you to seamlessly
        use the existing template methods just as they were originally written
        for pre-2.0.0 applications.
    </para>

    <para>
        The normal order of operations for a template engine are as follows:
    </para>

    <itemizedlist>
        <listitem>Create a template object</listitem>
        <listitem>Tell the template object where to find its files</listitem>
        <listitem>Assign variables to placeholders using the template
            object</listitem>
        <listitem>Process a template file using the template object</listitem>
    </itemizedlist>

    <para>
        In Cgiapp2, {@link Cgiapp2::tmpl_path() tmpl_path()} takes care of the
        first two steps, {@link Cgiapp2::tmpl_assign() tmpl_assign()} takes care
        of step 3, and {@link Cgiapp2::load_tmpl() load_tmpl()} does the
        final step (in Cgiapp2's case, simply returning the text from the
        template, as display is taken care of using the 
        {@link Cgiapp2::run() run()} method).
    </para>

    <para>The template methods are:</para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::tmpl_path() tmpl_path()}</listitem>
        <listitem>{@link Cgiapp2::tmpl_assign() tmpl_assign()}</listitem>
        <listitem>{@link Cgiapp2::load_tmpl() load_tmpl()}</listitem>
    </itemizedlist>

    <para>
        The standard template plugin classes included with the Cgiapp2
        distribution are:
    </para>

    <itemizedlist>
        <listitem>{@link Cgiapp2_Plugin_Smarty}</listitem>
        <listitem>{@link Cgiapp2_Plugin_Savant2}</listitem>
        <listitem>{@link Cgiapp2_Plugin_Savant3}</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.methods.application">
    <title>Application Methods</title>
    <para>
        The following methods provide core functionality for Cgiapp2: creation
        and modification of run modes, parameter and session storage, etc. Most
        of these methods are marked as 'final' and cannot be overridden.
    </para>
    <itemizedlist>
        <listitem>{@link Cgiapp2::param() param()}</listitem>
        <listitem>{@link Cgiapp2::delete() delete()}</listitem>
        <listitem>{@link Cgiapp2::s_param() s_param()}</listitem>
        <listitem>{@link Cgiapp2::s_delete() s_delete()}</listitem>
        <listitem>{@link Cgiapp2::mode_param() mode_param()}</listitem>
        <listitem>{@link Cgiapp2::run_modes() run_modes()}</listitem>
        <listitem>{@link Cgiapp2::start_mode() start_mode()}</listitem>
        <listitem>{@link Cgiapp2::prerun_mode() prerun_mode()}</listitem>
        <listitem>{@link Cgiapp2::error_mode() error_mode()}</listitem>
        <listitem>{@link Cgiapp2::get_current_runmode() get_current_runmode()}</listitem>
    </itemizedlist>
</refsect1>

<refsect1 id="{@id}.methods.static">
    <title>APPLICATION STATIC METHODS</title>
    <para>
        Cgiapp2 needed a number of custom methods in order to duplicate
        functionality from its perl counterpart, or to provide functionality
        that does not require class properties or access. They are all used
        internally, but can also be used by the class or as static methods using
        PHP's scoping operator, '::'.
    </para>

    <programlisting role="php">
    <![CDATA[
if (Cgiapp2::is_assoc_array($array)) echo "true\n";
    ]]>
    </programlisting>

    <itemizedlist>
        <listitem>{@link Cgiapp2::array_to_hash() array_to_hash()}</listitem>
        <listitem>{@link Cgiapp2::carp() carp()}</listitem>
        <listitem>{@link Cgiapp2::croak() croak()}</listitem>
        <listitem>{@link Cgiapp2::is_assoc_array() is_assoc_array()}</listitem>
        <listitem>
            {@link Cgiapp2::query() query(}: This method existed in the original
            perl to instantiate a CGI.pm query object, and was usually used to
            gain access to form parameters. Though primarily unnecessary in a
            PHP environment, typically you may wish to examine GET and/or POST
            variables, but not COOKIE variables, when validating form input. If
            you wanted all three (GET, POST, and COOKIE), you could use
            $_REQUEST. However, if you want only GET and POST, {@link
            Cgiapp2::query()} creates a protected variable, {@link
            Cgiapp2::$CGIAPP_REQUEST}, that contains an array of these two arrays
            merged. You can access it via query(), which returns a reference to
            it, or directly via $this->CGIAPP_REQUEST.
        </listitem>
        <listitem>
            {@link Cgiapp2::path_info() path_info()}: If PATH_INFO is supported
            in your SAPI, and present in your URL, returns the elements of it as
            an array. If passed an integer, it will return the element of
            PATH_INFO referenced by that index (a negative integer starts
            searching from the end of the array).
        </listitem>
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
