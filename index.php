<?php

include "simple_html_dom.php";

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;

//Load Composer's autoloader
require 'vendor/autoload.php';

class WebPage_notification{

    public $url;
    public $element;
    public $property;
    public $admin_mail;

    protected $db_name = 'database';
	protected $db_user = 'username';
	protected $db_pass = 'password';
	protected $db_host = 'localhost';
    
    function __construct($url, $element, $property, $admin_mail)
    {
        $this->url = $url;
        $this->element = $element;
        $this->property = $property;
        $this->admin_mail = $admin_mail;
        $this->mail = new PHPMailer(true);
    }

    function send_mail(){
        //Server settings
        $this->mail->SMTPDebug = 0;                      //Enable verbose debug output
        $this->mail->isSMTP();                                            //Send using SMTP
        $this->mail->Host       = 'smtp.website.sk';                     //Set the SMTP server to send through
        $this->mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $this->mail->Username   = 'noreply@website.sk';                     //SMTP username
        $this->mail->Password   = 'password';                               //SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $this->mail->Port       = 465;                                    //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        //Recipients
        $this->mail->setFrom('noreply@website.sk', 'Website SK Automat');
        $this->mail->addAddress($this->admin_mail);     //Add a recipient

        //Content
        $this->mail->isHTML(true);                                  //Set email format to HTML
        $this->mail->Subject = 'Website SK Upozornenie Pridanie noveho produktu</p>';
        $this->mail->Body    = '
        <p>Dobr&yacute; deň,</p>
        <p><br></p>
        <p>na Vami sledovan&uacute; str&aacute;nku <a href="'.$this->url.'" rel="noopener noreferrer" target="_blank">'.$this->url.'</a> bola pridan&aacute; nov&aacute; položka.</p>
        <p><br></p>
        <p><em></em><sub><em>Tento e-mail je generovan&yacute; automaticky, pros&iacute;m neodpovedajte naň.</em></sub></p>
        <p><em></em><sub><em>Hash: '.password_hash('salt', PASSWORD_DEFAULT).'</em></sub></p>';
        $this->mail->AltBody = 'Dobrý deň, na Vami sledovanú stránku ​'.$this->url.' bola pridaná nová položka. Tento e-mail je generovaný automaticky, prosím neodpovedajte naň.';

        $this->mail->send();
    }

    function db_connect(){

        $db_connection = new mysqli( $this->db_host, $this->db_user, $this->db_pass, $this->db_name );
		
		if (mysqli_connect_errno()) {
			printf("Connection failed: ", mysqli_connect_error());
			exit();
		};

        return $db_connection;
    }

    function get_data(){
        
        $link = $this->db_connect();

        $dom_content = file_get_html($this->url);
        $site_data = $dom_content->find($this->element);
        $property = $this->property;
        $query_status = false;

        foreach($site_data as $one_data){
            $cislo_produktu = $one_data->$property;
            $search_product_id = $link->query("SELECT `id` FROM `data` WHERE `cislo_produktu` = '$cislo_produktu' LIMIT 1;");

            if(!mysqli_num_rows($search_product_id)){
                $link->query("INSERT INTO `data` (`cislo_produktu`, `created_at`) VALUES ('$cislo_produktu', '".time()."');");
                $query_status = true;
            };
        };

        if($query_status){
            $this->send_mail();
            return "Nová položka";
        }else{
            return "Žiadna nová položka";
        };

    }
};


$item = new WebPage_notification("https://website.com", "a", "data-product-id", "mymail@website.sk");
$item->get_data();
