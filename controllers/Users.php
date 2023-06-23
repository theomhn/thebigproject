<?php

class Users extends Controller
{
    public function __construct()
    {
        // Charge le modèle user
        $this->model = $this->loadModel("User");
    }

    /**
     * Fonction de déconnexion
     */
    public function logout()
    {
        // Déconnecte l'utilisateur en supprimant le cookie d'authentification
        setcookie('authentication', "", -1, APP, false, true);
        echo json_encode(true);
    }

    /**
     * Fonction de connexion
     */
    public function login()
    {
        // Récupère les informations d'identification de l'utilisateur à partir de la requête POST
        $mail = $_POST['email'];
        $password = $_POST['password'];

        // Vérifie les informations d'identification et récupère l'utilisateur correspondant
        $user = $this->model->getByCredentials($mail, $password);

        if ($user) {
            // Définit un cookie d'authentification pour l'utilisateur
            $seconds = isset($_POST['rememberMe']) ? time() + 60 * 60 * 24 * 365 : 0;
            setcookie('authentication', $user['token'], $seconds, APP, false, true);

            echo json_encode($user['token']);
        } else {
            // Les informations d'identification sont incorrectes, renvoie une réponse d'erreur
            http_response_code(401);
            echo json_encode('Adresse mail ou mot de passe incorrect');
        }
    }

    public function authentificate()
    {
        // Vérifie si l'utilisateur est authentifié en vérifiant le cookie d'authentification
        if (isset($_COOKIE['authentication'])) {
            $user = $this->model->getByToken($_COOKIE['authentication']);
            if ($user && $user['active']) {
                return $user;
            }
        }
        return false;
    }

    private function sendValidationMail($user)
    {
        // Génère un lien de validation du compte utilisateur
        $link = "http://localhost/theBigProject/activate?token=" . $user['token'];
        return $link;
    }

    public function activate()
    {
        // Active le compte utilisateur en utilisant le jeton de validation passé en paramètre
        $res = $this->model->activate($_GET['token']);

        if ($res) {
            echo "Compte activé avec succès !";
        } else {
            echo "Aucun compte n'est en attente de validation avec ce jeton !";
        }
    }

    public function post()
    {
        // Crée un nouvel utilisateur avec les informations fournies dans la requête POST
        $obj = [
            'pseudo' => $_POST['pseudo'],
            'email' => $_POST['email'],
            'password' => $_POST['password']
        ];

        try {
            $user = $this->model->create($obj);
            $link = $this->sendValidationMail($user);
            echo json_encode($link);
        } catch (Exception $ex) {
            // Une exception s'est produite lors de la création de l'utilisateur, renvoie une réponse d'erreur
            http_response_code(400);
            echo json_encode("Adresse mail déjà inscrite !");
        }
    }
}
