<?php

session_start();

class Auth {

    public $conn;

    public $name;
    public $email;
    public $password;
    public $page;


    function __construct(string $connFile = __DIR__ . '/db_connection.php'){

        require $connFile;
        $this->conn = $conn;

        $this->name     = trim($_POST["name"] ?? '');
        $this->email    = trim($_POST["email"] ?? '');
        $this->password = $_POST["password"] ?? '';

        $this->page = basename($_SERVER['PHP_SELF']);
    }


    function checkUserLogin(): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($this->password, $user['password'])) {
            return $user;
        }

        return null;
    }

    function goToPage(): void
    {
        if ($this->page === 'login.php' && isset($_POST["sign-up-btn"])) {
            header("Location: signup.php");
            exit;
        }

        if ($this->page === 'signup.php' && isset($_POST["log-in-btn"])) {
            header("Location: login.php");
            exit;
        }
    }

    function registerUser(): string
    {
        if ($this->page !== 'signup.php' || !isset($_POST["sign-up-btn"])) {
            return '';
        }

        if ($this->name === '' || $this->email === '' || $this->password === '') {
            return "Please fill all the fields.";
        }

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            return "This email is already registered. Please sign in.";
        }

        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $this->name, $this->email, $hashedPassword);
        $stmt->execute();
        $stmt->close();

        $newUser = [
            'id'       => $this->conn->insert_id,
            'username' => $this->name,
            'email' => $this->email,
            'password'=> $hashedPassword
        ];

        $this->startSession($newUser);
        header("Location: dashboard.php");
        exit;
    }

    function logIn(): string
    {
        if ($this->page !== 'login.php' || !isset($_POST["log-in-btn"])) {
            return '';
        }

        if ($this->email === '' || $this->password === '') {
            return "Please enter your email and password.";
        }

        $user = $this->checkUserLogin();

        if ($user !== null) {
            $this->startSession($user);
            header("Location: dashboard.php");
            exit;
        }

        return "Wrong email or password.";
    }

    function startSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['userId']   = $user['id'];
        $_SESSION['username'] = $user['username'];
    }

    function logout(): void
    {
        if (isset($_POST["logout-btn"])) {
            session_unset();
            session_destroy();
            header('Location: dashboard.php');
            exit;
        }
    }

}

// MAIN CODE

$auth_functions = new Auth();

$auth_functions->goToPage();
$auth_functions->logout();
$signupError = $auth_functions->registerUser();
$loginError  = $auth_functions->logIn();
