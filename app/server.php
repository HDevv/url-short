<?php
# Global settings
declare(strict_types=1);
date_default_timezone_set('Europe/Paris');

# Global classes
class BasicSqlBuilder{
    private string $array_type;
    private string $query;
    private array $query_parameters;

    private function arrayTypeCheck(array $ARRAY, string $EXPECTED){
        if($EXPECTED !== 'associative' && $EXPECTED !== 'indexed'){
            throw new Exception("Invalid expected array type.");
        };
        if(array_keys($ARRAY) === range(0, count($ARRAY) - 1)){ //Checks if the given array is an indexed array...
            $this->array_type = 'indexed';
        }
        else{ //...else it's then an associative array.
            $this->array_type = 'associative';
        };
        if($this->array_type !== $EXPECTED){
            trigger_error(ucfirst($EXPECTED)." array expected, $this->array_type array given.", E_USER_ERROR);
        }
        else{return true;};
    }

    private function buildSelectQuery(string $TABLE, array $DATA, array $CONDITIONS = []) : string {
        if(sizeof($DATA) == 0){trigger_error("Datas expected, none given.", E_USER_ERROR);};
        if(self::arrayTypeCheck($DATA, 'indexed')){
            $this->query = "SELECT ";
            foreach($DATA as $key => $value){
                $this->query .= "$value, ";
            };
            $this->query = rtrim($this->query, ', ');
            $this->query .= " FROM $TABLE ";
            if(sizeof($CONDITIONS) > 0 && self::arrayTypeCheck($CONDITIONS, 'associative')){
                $this->query .= " WHERE ";
                foreach($CONDITIONS as $key => $value){
                    $this->query .= "$key = ? AND ";
                    $this->query_parameters[] = $value;
                };
                $this->query = rtrim($this->query, " AND ");
            };
            $this->query .= ';';
            return $this->query;
        };
    }

    private function buildUpdateQuery(string $TABLE, array $DATA, array $CONDITIONS = []) : string {
        if(sizeof($DATA) == 0){trigger_error("Datas expected, none given.", E_USER_ERROR);};
        if(self::arrayTypeCheck($DATA, 'associative')){
            $this->query = "UPDATE $TABLE SET ";
            foreach($DATA as $key => $value){
                $this->query .= "$key = ?, ";
                $this->query_parameters[] = $value;
            };
            $this->query = rtrim($this->query, ', ');
            if(sizeof($CONDITIONS) > 0 && self::arrayTypeCheck($CONDITIONS, 'associative')){
                $this->query .= " WHERE ";
                foreach($CONDITIONS as $key => $value){
                    $this->query .= "$key = ? AND ";
                    $this->query_parameters[] = $value;
                };
                $this->query = rtrim($this->query, " AND ");
            }
            else{trigger_error("Conditions expected, none given.", E_USER_ERROR);};
            $this->query .= ';';
            return $this->query;
        };
    }

    private function buildDeleteQuery(string $TABLE, array $CONDITIONS = []) : string {
        $this->query = "DELETE FROM $TABLE WHERE ";
        if(sizeof($CONDITIONS) > 0 && self::arrayTypeCheck($CONDITIONS, 'associative')){
            foreach($CONDITIONS as $key => $value){
                $this->query .= $key." = ? AND ";
                $this->query_parameters[] = $value;
            };
            $this->query = rtrim($this->query, " AND ");
        }
        else{trigger_error("Conditions expected, none given.", E_USER_ERROR);};
        $this->query .= ';';
        return $this->query;
    }

    private function buildInsertQuery(string $TABLE, array $DATA) : string {
        if(sizeof($DATA) == 0){trigger_error("Datas expected, none given.", E_USER_ERROR);};
        if(self::arrayTypeCheck($DATA, 'associative')){
            $this->query = "INSERT INTO $TABLE (";
            foreach($DATA as $key => $value){
                $this->query .= "$key, ";
            };
            $this->query = rtrim($this->query, ', ').') VALUES (';
            foreach($DATA as $key => $value){
                $this->query .= "?, ";
                $this->query_parameters[] = $value;
            };
            $this->query = rtrim($this->query, ', ').');';
            return $this->query;
        };
    }

    protected function buildQuery(PDO $DATABASE, string $TYPE, string $TABLE, array $DATA, array $CONDITIONS = []) : array {
        switch($TYPE){
            case "SELECT" :{
                $this->query = self::buildSelectQuery($TABLE, $DATA, $CONDITIONS);
                break;
            };
            case "UPDATE" :{
                $this->query = self::buildUpdateQuery($TABLE, $DATA, $CONDITIONS);
                break;
            };
            case "DELETE" :{
                if(sizeof($CONDITIONS) == 0){$CONDITIONS = $DATA;};
                $this->query = self::buildDeleteQuery($TABLE, $CONDITIONS);
                break;
            };
            case "INSERT" :{
                $this->query = self::buildInsertQuery($TABLE, $DATA);
                break;
            };
            default :{
                trigger_error('Invalid query type : must be "SELECT", "UPDATE", or "DELETE".', E_USER_ERROR);
                break;
            };
        };
        $query_execute = $DATABASE->prepare($this->query);
        $query_execute->execute($this->query_parameters);
        $this->query_parameters = [];
        $this->array_type = '';
        $this->query = '';
        if($result = $query_execute->fetch()){
            unset($query_execute);
            return $result;
        }
        else{return [];};
    }
};

class User extends BasicSqlBuilder{
    private int $id;
    private string $username;
    private string $mail;

    public function initUser(PDO $DATABASE, string $USERNAME, string $PASSWORD) : bool {
        $datas = [
            "id",
            "username",
            "password",
            "mail"
        ];
        $conditions = [
            "username" => $USERNAME
        ];
        $user_info = self::buildQuery($DATABASE, "SELECT", "user", $datas, $conditions);
        if(
            sizeof($user_info) > 0 &&
            password_verify($PASSWORD, $user_info["password"])
        ){
            $this->id = $user_info['id'];
            $this->username = $user_info['username'];
            $this->mail = $user_info['mail'];
            return true;
        }
        else{return false;};
    }
    public function getID() : int {return $this->id;}
    public function getUsername() : string {return $this->username;}
    public function getMail() : string {return $this->mail;}
    public function getLinks(PDO $DATABASE) : array {
        $user_links = [];
        $user_links_query = $DATABASE->prepare("SELECT * FROM link WHERE owner_id = ?;");
        $user_links_query->execute([$this->id]);
        while($user_link = $user_links_query->fetch()){
            $user_links[] = $user_link;
        };
        return $user_links;
    }
    public function setMail(PDO $DATABASE, string $MAIL) : void {
        $datas = ["mail" => $MAIL];
        $conditions = ["id" => $this->id];
        $user_info = self::buildQuery($DATABASE, "UPDATE", "user", $datas, $conditions);
        $this->mail = $MAIL;
    }
    public function setPassword(PDO $DATABASE, string $PASSWORD) : void {
        $datas = ["password" => $PASSWORD];
        $conditions = ["id" => $this->id];
        $user_info = self::buildQuery($DATABASE, "UPDATE", "user", $datas, $conditions);
    }
};

class Link extends BasicSqlBuilder {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }
    
    public function addLink(int $ownerId, string $originalUrl): void {
        # Getting registering informations
        $shortUrl = $this->generateShortUrl();
        $linkData = [
            "original" => $originalUrl,
            "short" => $shortUrl,
            "clicks" => 0,
            "owner_id" => $ownerId,
            "state" => true
        ];
        $this->buildQuery($this->conn, "INSERT", "link", $linkData);
    }

    public function addFile(int $owner_id, string $file_name) : void {
        # Getting registering informations
        $shortUrl = $this->generateShortUrl();
        $linkData = [
            "attached_file_name" => $file_name,
            "short" => $shortUrl,
            "clicks" => 0,
            "owner_id" => $owner_id,
            "state" => true
        ];
        $this->buildQuery($this->conn, "INSERT", "link", $linkData);
    }
    
    private function generateShortUrl(): string {
        $length = 6;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $shortCode = '';

        for($i = 0; $i < $length; $i++){
            $shortCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $shortCode;
    }
}

# Global use functions


# Global use variables


# Session and connection to database init
session_start();
$errors = []; //Used to collect errors if some happen.
$pdo_options = [ //Some options to configure the PDO connection.
    PDO::ATTR_EMULATE_PREPARES => false, //Turn off emulation mode for "real" prepared statements.
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //Turn on errors in the form of exceptions.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //Makes the default fetch be an associative array.
];
try{
    $conn = new PDO("mysql:host=localhost:3307;dbname=url_shortener;charset=utf8mb4", "root", "", $pdo_options); //Connection to the database (Windows).
    //$conn = new PDO("mysql:host=mariadb:3306;dbname=url_shortener;charset=utf8mb4", "root", "root", $pdo_options); //Connection to the database (GNU/Linux).
}
catch(Exception $e){echo "Connection failed : ".$e->getMessage();};
if(isset($_SESSION['user'])){$user = $_SESSION['user'];}; //Gets the current user if one is already connected.

# User login
if(isset($_POST['login'])){ //Check if Login button is pressed.
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user = new User();
    if($user->initUser($conn, $username, $password)){
        $_SESSION['user'] = $user;
        header("Refresh: 0; url=main.php");
    }
    else{
        $_SESSION['badpass'] = true;
        header("Refresh: 0; url=login.php");
    };
};

# New link creation
if(isset($_POST['new_link'])){
    $originalUrl = trim($_POST['original_url']);
    $user_link = new Link($conn);
    $user_link->addLink($user->getID(), $originalUrl);
};

# User registration
if(isset($_POST['register'])){
    # Getting registering informations
    $username = trim($_POST["username"]);
    $mail = trim($_POST["mail"]);
    $password = $_POST["password"];
    $password_confirmation = $_POST['password_confirmation'];
    
    # Checking if passwords match
    if($password !== $password_confirmation){
        $errors[] = "Les mots de passe ne correspondent pas.";
        ?>
        <script>
            alert("Les mots de passe ne correspondent pas.");
        </script>
        <?php
    };

    # Checking if username is already used
    $username_check_query = $conn->prepare("SELECT id FROM user WHERE username=?;");
    $username_check_query->execute([$username]);
    if($username_check_query->rowCount() > 0){
        $errors[] = "Nom d'utilisateur déjà utilisé.";
        ?>
        <script>
            alert("Nom d'utilisateur déjà utilisé.");
        </script>
        <?php
    };

    # Checking if mail address is already used
    $mail_check_query = $conn->prepare("SELECT id FROM user WHERE mail=?;");
    $mail_check_query->execute([$mail]);
    if($mail_check_query->rowCount() > 0){
        $errors[] = "Adresse mail déjà utiliseé.";
        ?>
        <script>
            alert("Adresse mail déjà utilisée.");
        </script>
        <?php
    };

    # Register the user if no error occured
    if(count($errors) == 0){
        # Preparing SQL query
        $new_user_query = "
            INSERT INTO user (username, mail, password)
            VALUES (?, ?, ?);
        ";
        $new_user_query_parameters = [
            $username,
            $mail,
            password_hash($password, PASSWORD_DEFAULT)
        ];

        # Executing the query
        $insert_query = $conn->prepare($new_user_query);
        $insert_query->execute($new_user_query_parameters);
        ?>
        <script>
            alert("Inscription réussie !");
        </script>
        <?php

        # Automatically connect the user once the account created
        $user = new User();
        $user->initUser($conn, $username, $password);
        $_SESSION['user'] = $user;
        header("Refresh: 0; url=main.php");
    };
};

# Index script handling
if(basename($_SERVER['PHP_SELF']) === "index.php"){
    # Redirecting short links to original ones
    if(!empty($_GET)){
        $i = 0;
        foreach($_GET as $key => $value){
            if($value !== '' || $i !== 0){
                throw new Exception("Error Bad Request", 500);
            };
            $short_link = $key;
            $i++;
        };
        unset($i);
        $link_query = $conn->prepare('SELECT original, state, id FROM link WHERE short = ?;');
        $link_query->execute([$short_link]);
        if($link = $link_query->fetch()){
            if($link['state'] === 1){
                $click_increment = $conn->prepare('UPDATE `link` SET `clicks` = (`clicks` + 1) WHERE `link`.`id` = ?;');
                $click_increment->execute([$link['id']]);
                header('Refresh: 0; url='.$link['original']);
            }
            else{header('Location: ./errors/disabled.html');};
        }
        else{header('Location: ./errors/not_found.html');};
    }
    
    # Redirecting users to app
    else{
        # Redirections
        if(
            basename($_SERVER['PHP_SELF']) === "login.php" || 
            basename($_SERVER['PHP_SELF']) === "register.php"
        ){
            if(isset($_SESSION['user'])){header("Location: main.php");};
        }
        else{
            if(isset($_SESSION['user'])){$user = $_SESSION['user'];}
            else{header("Location: login.php");};
        };
    };
};

# File upload handling
if(isset($_POST['file_upload']) && isset($_FILES["upload"])){
    if(is_uploaded_file($_FILES["upload"]["tmp_name"])) {
        if($_FILES["upload"]["error"] == UPLOAD_ERR_OK){
            $upload_type = mime_content_type($_FILES["upload"]["tmp_name"]);
            if(str_starts_with($upload_type, "image/")){
                $upload_name = bin2hex(random_bytes(5)).".".str_replace("image/", "", $upload_type);
                if(!is_dir("uploads")){mkdir("uploads");};
                move_uploaded_file(
                    $_FILES["upload"]["tmp_name"],
                    "uploads/".$upload_name
                );

                # Registering the file into database
                $user_file = new Link($conn);
                $user_file->addFile($user->getID(), $upload_name);
            }
            else{
                $_SESSION['badfileformat'] = true;
                header("Refresh: 0; url=main.php");
            };
        }
        else{
            $_SESSION['uploaderror'] = true;
            header("Refresh: 0; url=main.php");
        };
    };
};