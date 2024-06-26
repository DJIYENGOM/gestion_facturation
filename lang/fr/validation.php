<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | such as the size rules. Feel free to tweak each of these messages.n
    |
    */

    'accepted' => 'Le champ :attribute doit être accepté.',
    'accepted_if' => 'Le champ :attribute doit être accepté lorsque :other vaut :value.',
    'active_url' => 'Le champ :attribute n\'est pas une URL valide.',
    'after' => 'Le champ :date d\'expiration doit être une date postérieure à ajourd\'hui.',
    'after_or_equal' => 'Le champ :attribute doit être une date après ou égale à :date.',
    'alpha' => 'Le champ :attribute doit seulement contenir des lettres.',
    'alpha_dash' => 'Le champ :attribute doit seulement contenir des lettres, des chiffres et des tirets.',
    'alpha_num' => 'Le champ :attribute doit seulement contenir des chiffres et des lettres.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'ascii' => 'Le champ :attribute ne doit contenir que des caractères alphanumériques à un octet et des symboles.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'before_or_equal' => 'Le champ :attribute: doit être une date avant ou égale à :date.',
    'between' => [
        'numeric' => 'La valeur de :attribute doit être comprise entre :min et :max.',
        'file' => 'Le fichier :attribute doit avoir une taille entre :min et :max kilo-octets.',
        'string' => 'Le texte :attribute doit avoir entre :min et :max caractères.',
        'array' => 'Le tableau :attribute doit avoir entre :min et :max éléments.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'Le champ de confirmation :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute n\'est pas une date valide.',
    'date_equals' => 'Le champ :attribute doit être une date égale à :date.',
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'decimal' => 'Le champ :attribute doit avoir des décimales :decimal.',
    'declined' => 'Le champ :attribute doit être refusé.',
    'declined_if' => 'Le champ :attribute doit être refusé lorsque :other vaut :value.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit avoir :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit avoir entre :min and :max chiffres.',
    'dimensions' => 'Le champ :attribute a des dimensions d\'image non valides.',
    'distinct' => 'Le champ a une valeur en double.',
    'doesnt_end_with' => 'Le champ :attribute ne peut pas se terminer par l\'un des éléments suivants : :values.',
    'doesnt_start_with' => 'Le champ :attribute ne peut pas se commencer par l\'un des éléments suivants : :values.',
    'email'  => "Le champ :attribute doit être une adresse email valide.",
    'ends_with' => 'Le champ :attribute doit se terminer par l\'une des valeurs suivantes : :values.',
    'enum' => 'Le champ :attribute sélectionné est invalide.',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => "Le champ :attribute est obligatoire.",
    'gt' => [
        'array'  => 'Le champ :attribute doit avoir plus de :value éléments.',
        'file' => 'Le champ :attribute doit être supérieur à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'string' => 'Le champ :attribute doit être supérieur à :value caractères.',
    ],
    'gte' => [
        'array' => 'Le champ :attribute doit avoir :value éléments ou plus.',
        'file' => 'Le champ :attribute doit être supérieur ou égal à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'string' => 'Le champ :attribute doit être supérieur ou égal à :value caractères.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'Le champ :attribute est invalide.',
    'in_array' => 'Le champ :attribute n\'existe pas dans :other.',
    'integer' => 'Le champ :attribute doit être un entier (un nombre sans virgule).',
    'ip' => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4' => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6' => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json' => 'Le champ :attribute doit être une chaîne JSON valide.',
    'lowercase' => 'Le champ :attribute doit être en minuscules.',
    'lt' => [
        'array' => 'Le champ :attribute doit avoir moins de :value éléments.',
        'file' => 'Le champ :attribute doit être inférieur à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'string' => 'Le champ :attribute doit être inférieur à :value caractères.',
    ],
    'lte' => [
        'array' => 'Le champ :attribute ne doit pas avoir plus de :value éléments.',
        'file' => 'Le champ :attribute doit être inférieur ou égal à :value kilobytes.',
        'numeric' => 'Le champ :attribute doit être inférieur ou égal à :value.',
        'string' => 'Le champ :attribute doit être inférieur ou égal à :value caractères.',
    ],
    'mac_address' => 'Le champ :attribute doit être une adresse MAC valide.',
    'max' => [
        'array' => 'Le tableau :attribute ne peut avoir plus de :max éléments.',
        'file' => 'La taille du fichier :attribute ne peut être supérieure à :max kilo-octets.',
        'numeric' => 'La valeur de :attribute ne peut être supérieure à :max.',
        'string' => 'Le texte de :attribute ne peut contenir plus de :max caractères.',
    ],
    'max_digits' => 'Le champ :attribute ne doit pas avoir plus de :max chiffres.',
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'min' => [
        'numeric' => 'La valeur de :attribute ne peut être inférieure à :min.',
        'file' => 'La taille du fichier :attribute ne peut être inférieure à :min kilo-octets.',
        'string' => 'Le texte du champ :attribute doit contenir au moins :min caractères.',
    ],
    'min_digits' => 'Le champ :attribute doit avoir au moins :min chiffres.',
    'missing' => 'Le champ :attribute doit être manquant.',
    'missing_if' => 'Le champ :attribute doit être manquant lorsque :other vaut :value.',
    'missing_unless' => 'Le champ :attribute doit être manquant sauf si :other est :value.',
    'missing_with' => 'Le champ :attribute doit être manquant lorsque :value est présent.',
    'missing_with_all' => 'Le champ :attribute doit être manquant lorsque :values sont présents.',
    'multiple_of' => 'Le champ :attribute doit être un multiple de :value.',
    'not_in' => 'Le champ :attribute sélectionné n\'est pas valide.',
    'not_regex' => 'Le champ :attribute a un format invalide.',
    'numeric' => 'Le champ :attribute doit contenir un nombre sans virgule.',
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Le :attribute donné est apparu dans une fuite de données. Veuillez choisir un autre :attribute.',
    ],
    'present' => 'Le champ :attribute doit être present.',
    'prohibited' => 'Le champ :attribute est interdit.',
    'prohibited_if' => 'Le champ :attribute est interdit lorsque :other vaut :value.',
    'prohibited_unless' => 'Le champ :attribute est interdit sauf si :other est dans :values.',
    'prohibits' => 'Le champ :attribute interdit la présence de :other.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_array_keys' => 'Le champ :attribute doit contenir des entrées pour : :values.',
    'required_if' => 'Le champ :attribute est obligatoire quand la valeur de :other est :value.',
    'required_if_accepted' => 'Le champ :attribute est obligatoire lorsque :other est accepté.',
    'required_unless' => 'Le champ :attribute est obligatoire sauf si :other est dans :values.',
    'required_with' => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_with_all' => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_without' => 'Le champ :attribute est obligatoire quand :values n\'est pas présent.',
    'required_without_all' => 'Le champ :attribute est requis quand aucun de :values n\'est présent.',
    'same' => 'Les champs :attribute et :other doivent être identiques.',
    'size' => [
        'numeric' => 'La valeur de :attribute doit être :size.',
        'file' => 'La taille du fichier de :attribute doit être de :size kilo-octets.',
        'string' => 'Le texte de :attribute doit contenir :size caractères.',
        'array' => 'Le tableau :attribute doit contenir :size éléments.',
    ],
    'starts_with' => 'Le :attribute doit commencer par l\'un des éléments suivants : :values.',
    'string' => 'Le :attribute doit être une chaîne.',
    'timezone' => 'Le :attribute doit être un fuseau horaire valide.',
    'unique' => 'Le :attribute a déjà été pris.',
    'uploaded' => 'Le :attribute n\'a pas pu être téléchargé.',
    'uppercase' => 'Le :attribute doit être en majuscule.',
    'url' => 'Le :attribute doit être une URL valide.',
    'ulid' => 'Le :attribute doit être un ULID valide.',
    'uuid' => 'Le :attribute doit être un UUID valide.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        
        "nom"=> [
            'regex' => 'Le champ nom est invalide , pas de caracteres speciaux et au moins 2 caractères',
        ],

        "name"=> [
            'regex' => 'Le champ nom est invalide, pas de caracteres speciaux et au moins 2 caractères',
        ],

        "nom_client"=> [
            'regex' => 'Le champ nom est invalide, pas de caracteres speciaux et au moins 2 caractères',
        ],

        "prenom_client"=> [
            'regex' => 'Le champ prenom est invalide, pas de caracteres speciaux et au moins 2 caractères',
        ],

        "telephone"=> [
            'regex' => 'Le téléphone doit commencer par 7 suivi de 8 chiffres. Exemple : 770000000',
        ],

        "password"=> [
            'regex' => 'Le mot de passe doit avoir au moins 8 caractères et contenir au moins une lettre, un chiffre et un caractere special',
        ],

        "prix_unitaire"=> [
            'regex' => 'Le champ prix unitaire est invalide ',
        ],

        "nom_categorie"=> [
            'regex' => 'Le champ categorie est invalide ',
        ],

        'id_user' => [
            'exists' => 'il ya un probleme avec cet utilisateur qui est connecté',
        ],

        'description_entreprise' => [
            'regex' => 'Le champ description est invalide maximum 255 caractères',
        ],

        'logo' => [
            'regex' => 'Le champ logo est invalide, le type de fichier doit etre jpg, png ou jpeg et max:2048', 
        ],

        'nom_entreprise' => [
            'regex' => 'Le champ nom de l\'entreprise est invalide, pas de caracteres speciaux et au moins 2 caractères',
        ],

        'adress_entreprise' => [
            'regex' => 'Le champ adresse est invalide, au moins 2 caractères',
        ],

        'tel_entreprise' => [
            'regex' => 'Le champ telephone est invalide, au moins 9 caractères',
        ],

        'tel_client' => [
            'regex' => 'Le champ telephone est invalide, au moins 9 caractères',
        ],

        'facture_id'=> [
            'regex' => 'faut d\'abord associer à un payment',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        "activity" => "activité",
        "activities" => "activités",
        "address" => "adresse",
        "addresses" => "adresses",
        "age" => "âge",
        "ages" => "âges",
        "amount" => "montant",
        "amounts" => "montants",
        "answer" => "réponse",
        "answers" => "réponses",
        "available" => "disponible",
        "availables" => "disponibles",
        "barcode" => "code-barres",
        "barcodes" => "codes-barres",
        "birth_date" => "date de naissance",
        "brand" => "marque",
        "brands" => "marques",
        "brand_name" => "nom de la marque",
        "buying_price" => "prix d'achat",
        "category" => "catégorie",
        "categories" => "catégories",
        "city" => "ville",
        "cities" => "villes",
        "civility" => "civilité",
        "civilities" => "civilités",
        "comment" => "commentaire",
        "comments" => "commentaires",
        "company" => "entreprise",
        "companies" => "entreprises",
        "confirmed" => "confirmé",
        "confirmed_at" => "confirmé le",
        "content" => "contenu",
        "contents" => "contenus",
        "country" => "pays",
        "countries" => "pays",
        "customer" => "client",
        "customers" => "clients",
        "day" => "jour",
        "days" => "jours",
        "date_end" => "date de fin",
        "date_start" => "date de début",
        "directory" => "dossier",
        "directory_name" => "nom du dossier",
        "directories" => "dossiers",
        "directories_name" => "nom des dossiers",
        "directories_names" => "noms des dossiers",
        "email_banned" => "email banni",
        "email_confirmed" => "email confirmé",
        "email_validated" => "email validé",
        "email_prohibited" => "email inerdit",
        "emails_banned" => "emails bannis",
        "emails_confirmed" => "emails confirmés",
        "emails_validated" => "emails validés",
        "emails_prohibited" => "emails inerdits",
        "file" => "fichier",
        "files" => "fichiers",
        "first_name" => "prénom",
        "first_names" => "prénoms",
        "gender" => "genre",
        "genders" => "genres",
        "hour" => "heure",
        "hours" => "heures",
        "is_active" => "est actif ?",
        "is_banned" => "bannir ?",
        "job" => "métier",
        "jobs" => "métiers",
        "last_name" => "nom de famille",
        "last_names" => "noms de famille",
        "link" => "lien",
        "links" => "liens",
        "month" => "mois",
        "name" => "nom",
        "names" => "noms",
        "office" => "bureau",
        "offices" => "bureaux",
        "other" => "autre",
        "others" => "autres",
        "paid_at" => "payé le",
        "password" => "mot de passe",
        "password_confirmation" => "confirmation du mot de passe",
        "password_current" => "mot de passe actuel",
        "passwords" => "mots de passe",
        "phone" => "téléphone",
        "phones" => "téléphones",
        "postal_code" => "code postal",
        "price" => "prix",
        "published_at" => "publié le",
        "quantity" => "quantité",
        "quantities" => "quantités",
        "rate" => "taux",
        "rates" => "taux",
        "response" => "réponse",
        "responses" => "réponses",
        "role" => "rôle",
        "roles" => "rôles",
        "second" => "seconde",
        "seconds" => "secondes",
        "siren_number" => "numéro de siren",
        "siret_number" => "numéro de siret",
        "size" => "taille",
        "sizes" => "tailles",
        "status" => "statut",
        "statuses" => "statuts",
        "street" => "rue",
        "subfolder" => "sous-dossier",
        "subfolders" => "sous-dossiers",
        "subdirectory" => "sous-dossier",
        "subdirectories" => "sous-dossiers",
        "subject" => "sujet",
        "subjects" => "sujets",
        "summary" => "chapô",
        "summarys" => "chapôs",
        "supplier" => "fournisseur",
        "suppliers" => "fournisseurs",
        "tax" => "taxe",
        "time" => "heure",
        "title" => "titre",
        "titles" => "titres",
        "user" => "utilisateur",
        "users" => "utilisateurs",
        "username" => "pseudo",
        "usernames" => "pseudos",
        "value" => "valeur",
        "values" => "valeurs",
        "vat" => "TVA",
        "vat_rate" => "taux de TVA",
        "website" => "site web",
        "websites" => "sites web",
        "year" => "année",
        "years" => "années",
    ],

];