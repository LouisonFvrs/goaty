<?php

namespace controllers;

use controllers\base\WebController;
use models\CategorieModel;
use models\CommentaireModel;
use models\ExemplaireModel;
use models\LocalisationModel;
use models\RessourceModel;
use utils\SessionHelpers;
use utils\Template;

class CatalogueController extends WebController
{

    private RessourceModel $ressourceModel;
    private CategorieModel $categorieModel;
    private ExemplaireModel $exemplaireModel;
    private CommentaireModel $commentaireModel;
    private LocalisationModel $localisationModel;

    function __construct()
    {
        $this->ressourceModel = new RessourceModel();
        $this->exemplaireModel = new ExemplaireModel();
        $this->categorieModel = new CategorieModel();
        $this->commentaireModel = new CommentaireModel();
        $this->localisationModel = new LocalisationModel();

    }

    /**
     * Affiche la liste des ressources.
     * @param string $type
     * @return string
     */
    function liste(string $categorie): string
    {
        // Récupération de toutes les categories
        $categories = $this->categorieModel->getAll();

        // L'utilisateur n'est pas connecté on affiche les toutes les ressources
        if (! SessionHelpers::isLogin()) {
            // L'utilisateur souhaite visualiser toutes les ressources
            if ($categorie == "all") {

                $catalogue = $this->ressourceModel->getAll();

                return Template::render("views/catalogue/liste.php", array("titre" => "Ensemble du catalogue", "catalogue" => $catalogue, "categories" => $categories));
            }
            // L'utilisateur souhaite visualiser certaines type de ressource ex : BD, Livre
            else if ($categorie == "tri" && isset($_GET['categories']))
            {

                $catalogue = $this->ressourceModel->getRessourceFilter($_GET['categories']);

                return Template::render("views/catalogue/liste.php", array("titre" => "Ensemble du catalogue", "catalogue" => $catalogue, "categories" => $categories));
            }
            else
            {
                $catalogue = $this->ressourceModel->getAll();
                return Template::render("views/catalogue/liste.php", array("titre" => "Ensemble du catalogue", "catalogue" => $catalogue, "categories" => $categories));
            }
        } else {
            // l'utilisateur est connecté

            $user = SessionHelpers::getConnected();
            $ville = $this->localisationModel->getVilleOfuser($user->idLocalisation);

            if ($categorie == "all") {

                $catalogue = $this->ressourceModel->getAllForLocation($user->idLocalisation);

                return Template::render("views/catalogue/liste.php", array("titre" => "Ensemble du catalogue", "catalogue" => $catalogue, "categories" => $categories, "ville" => $ville));
            }
            // L'utilisateur souhaite visualiser certaines type de ressource ex : BD, Livre
            else if ($categorie == "tri" && isset($_GET['categories']))
            {

                $catalogue = $this->ressourceModel->getRessourceFilterForLocation($_GET['categories'], $user->idLocalisation);

                return Template::render("views/catalogue/liste.php", array("titre" => "Ensemble du catalogue", "catalogue" => $catalogue, "categories" => $categories, "ville" => $ville));
            }
            else
            {
                $catalogue = $this->ressourceModel->getAllForLocation($user->idLocation);
                return Template::render("views/catalogue/liste.php", array("titre" => "Ensemble du catalogue", "catalogue" => $catalogue, "categories" => $categories, "ville" => $ville));
            }
        }
    }

    /**
     * Affiche le détail d'une ressource.
     * @param int $id
     * @return string
     */
    function detail(int $id): string
    {
        // Récupération de la ressource
        $ressource = $this->ressourceModel->getOne($id);
        $user = SessionHelpers::getConnected();


        if ($ressource == null) {
            $this->redirect("/");
        }


        // /! vérifier que l'on est connecté
        // création d'un commentaire
        if (isset($_POST['com']) && isset($_POST['note'])) {
            $this->commentaireModel->createCommentaire(htmlspecialchars($_POST['com']), htmlspecialchars($_POST['note']), $user->idemprunteur, $ressource->idressource);
        }

        // Récupération des exemplaires de la ressource
        $exemplaires = $this->exemplaireModel->getByRessource($id);
        $exemplaire = null;

        // Pour l'instant, on ne gère qu'un exemplaire par ressource.
        // Si on en trouve plusieurs, on prend le premier.
        if ($exemplaires && sizeof($exemplaires) > 0) {
            $exemplaire = $exemplaires[0];
        }

        // Récupération des commentaires de la ressources
        $commentaires = $this->commentaireModel->getComByRessources($id);


        return Template::render("views/catalogue/detail.php", array("ressource" => $ressource, "exemplaire" => $exemplaire, "commentaires" => $commentaires));
    }
}