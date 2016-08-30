ATTENTION : il est nécessaire d'effectuer un correctif dans dolibarr (il sera présent dans la prochaine version 3.5.2).

L'erreur à corriger se trouve dans le fichier /htdocs/fourn/class/fournisseur.class.php

Dans la fonction  function ListArray() ligne 180
--> ajouter au début "global $user;" 
--> remplacer toutes les occurences de "$this->user->" par "$user"


1.0.3 : hearder forwarding correct issue