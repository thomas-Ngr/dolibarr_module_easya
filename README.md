Module Easya
==================
Module spécifique pour Easya.


Utilisation du script
---------------------

Le module Easya contient un script qui permet de charger des constantes depuis un fichier CSV.

Il s'utilise en ligne de commande, dans ce cas les constantes seront chargées dans l'entité 0 (toutes les entités)
`php custom/easya/scripts/load_parameters.php path/to/constants_file.csv`

Ou depuis l'interface admin de Easya, dans ce cas les constantes seront appliquées à l'entité courante.

Voici un exemple de fichier CSV (la première ligne peut être conservée. "type" est le plus souvent "chaine"):
```
name, value, type, visible, note
OBLYON_COLOR_TOPMENU_BCKGRD, #eb4c42, chaine, 1, "test color"
```