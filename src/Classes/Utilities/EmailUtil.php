<?php

namespace Classes\Utilities;

define("MODERATOR", "newmang@nrel.colostate.edu");
define("UTILHTMLHEADER", "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n");
define("UTILTEXTHEADER", "MIME-Version: 1.0\r\nContent-type: text/plain; charset=iso-8859-1\r\n");
define("UTIL_CITSCI_HEADER", "From: CitSci.org <webmaster@citsci.org>\r\n");

define("CITSCI_EMAIL_BODYSTART","<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 3.2 Final//EN'>
		<html>
		<head>
		<title>CitSci.org</title>
		<link rel='stylesheet' href='http://www.citsci.org/cwis438/stylesheets/citsci.org.css' type='text/css'>
		</head>
                    <body bgcolor='#F5F4E8'>
                        <table width='600' cellpadding='10' cellspacing='10' bgcolor='#F3F7FA' align='center'>
                            <tr>
                                <td>
                                    <center><img src='http://www.citsci.org/WebContent/WS/citsci/images/MailChimpLogo.gif' width='200'></center>
                                        <br/><br>");

define("CITSCI_EMAIL_BODYEND","<br><br></td></tr></table></body></html>");

class EmailUtil {

    public static function SendHTMLMsg($Email, $Subject, $Message, $WebsiteID) {
        $Headers = UTILHTMLHEADER;
        // additional headers 
        // $headers.="To: ".$addr."\r\n";
        $Headers.=UTIL_CITSCI_HEADER;

        $Mailsent = mail($Email, $Subject, $Message, $Headers);
        return $Mailsent;
    }

}
