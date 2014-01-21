<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns="http://www.w3.org/1999/xhtml"
    xmlns:php="http://php.net/xsl">
  <xsl:output method="text" indent="yes" omit-xml-declaration="yes"
      media-type="text/plain" encoding="utf-8"/>
  <!-- Main template -->
  <xsl:template match="/">
    <xsl:variable name="var2" select="//item" />
    <xsl:value-of select="php:functionString('strtolower', $var2)" />
    <xsl:value-of select="$var1" />
  </xsl:template>
</xsl:stylesheet>
