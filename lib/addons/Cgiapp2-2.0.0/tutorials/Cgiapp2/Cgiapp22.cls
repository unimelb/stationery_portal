<?xml version="1.0" encoding="iso-8859-1" ?>
<!-- $Revision:$ -->

<refentry id="{@id}.abstract">
<refnamediv>
    <refname>Abstract</refname>
</refnamediv>

<refsect1 id="{@id}.abstract.desc">
    <para>
        The guiding philosophy behind Cgiapp2 is that a web-based application can
        be organized into a specific set of "Run Modes." Each Run Mode is
        roughly analogous to a single screen (a form, some output, etc.).  All
        the Run Modes are managed by a single "Application Class" which is a PHP
        class.  In your web server's document space there is an "Instance
        Script" which is called by the web server.
    </para>
    <para>
        This methodology is an inversion of the "Embedded" philosophy (typical
        PHP, ASP, JSP, EmbPerl, Mason, etc.) in which there are "pages" for each
        state of the application, and the page drives functionality.  In Cgiapp2,
        form follows function -- the Application Class drives pages, and the
        code for a single application is in one place; not spread out over
        multiple "pages".  If you feel that Embedded architectures are
        confusing, unorganized, difficult to design and difficult to manage,
        Cgiapp2 is the methodology for you!
    </para>
    <para>
        The only requirement for Cgiapp2 is a working PHP installation; as of
        version 2.0.0, there is a minimum requirement of PHP version 5. If you
        wish to use a templating engine, Cgiapp2 supports Smarty "out of the
        box", but all template methods can be overridden should you desire to
        use a different templating engine, and the various template hooks in the
        callback system (see the Callback Hook System page for more details).
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
