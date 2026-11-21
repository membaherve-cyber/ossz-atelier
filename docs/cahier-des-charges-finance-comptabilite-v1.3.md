# Cahier des charges fonctionnel – Module Finance & Comptabilité

Plateforme de gestion d'un atelier de couture · Version 1.3 (synchronisation des versions 1.1 et 1.2)

## 1. Présentation du projet

Ce cahier des charges définit les fonctionnalités attendues du module Finance & Comptabilité d'une plateforme de gestion d'un atelier de couture. La plateforme dispose déjà des modules Clients, Commandes, Production, Stocks et Utilisateurs/Personnel. Le module Finance & Comptabilité s'y intègre pour centraliser les opérations financières, comptables et analytiques, sans ressaisie ni duplication des données.

### 1.1. Hypothèses d'intégration

Le module Finance & Comptabilité s'ajoute à une plateforme existante. Il **lit** les données des modules existants et ne les recrée pas : fiches de mesures, dates promises, statuts d'avancement des commandes, essayages, tâches de production, stocks et personnel. Le présent cahier des charges ne couvre que la finance, la comptabilité et la traçabilité des coûts.

À vérifier avant le développement : que les modules existants exposent bien ces données (statut « prêt / retiré » et date promise d'une commande, tâches validées par pièce, type de tâche pouvant être marqué « reprise »).

### 1.2. Principe de saisie minimale et de calcul automatique

Les utilisateurs qui saisissent les informations n'ont pas besoin de connaître la comptabilité. Ils ne saisissent que des **faits simples** ; tout le reste est calculé par la plateforme.

**Ce que l'utilisateur saisit à la main :**

- **Un achat :** fournisseur, matière, quantité, prix unitaire, date, mode de paiement.
- **Une remise de matière à un employé :** commande, pièce, employé, matière, quantité.
- **Les déclarations de l'employé :** quantité consommée et quantité restituée.
- **Un paiement reçu ou versé :** montant, date, compte (caisse, banque, Mobile Money), personne concernée.
- **Une dépense courante :** catégorie, montant, date, pièce justificative.

**Ce que la plateforme calcule seule, sans saisie :**

- le montant total d'un achat (quantité × prix unitaire) et la mise à jour du stock ;
- la valeur de la matière sortie ou consommée, selon la méthode de valorisation configurée ;
- la perte (remis − consommé − restitué), voir 5.10 ;
- les soldes : stock, caisse et comptes, solde restant dû par client, dettes fournisseurs ;
- les coûts par commande, pièce, opération et employé, et la marge de chaque commande ;
- les montants de rémunération à partir des tâches validées ;
- les écritures comptables (voir section 6) et tous les rapports et tableaux de bord.

Règles complémentaires :

- Une information déjà connue du système (client, commande, prix d'une matière, tarif d'une tâche) n'est jamais ressaisie : elle est proposée à la sélection.
- Les formulaires utilisent un vocabulaire d'atelier (« j'ai payé », « j'ai reçu », « matière remise »), sans termes comptables comme débit ou crédit.
- Le système refuse ou signale les saisies incohérentes (quantité négative, prix absent, total différent du paiement) avec un message en langage clair.
- Une correction se fait par une opération corrective tracée (voir section 10), jamais en modifiant un calcul à la main.

## 2. Objectifs

Le module doit permettre de :

- Enregistrer et suivre les recettes et dépenses.
- Gérer les encaissements, décaissements et comptes de trésorerie.
- Suivre les créances clients et les dettes fournisseurs.
- Gérer les rémunérations du personnel.
- Produire les documents de comptabilité générale.
- Calculer les coûts de revient et les marges par commande.
- Suivre les coûts des matières premières et de la main-d'œuvre.
- Mesurer la rentabilité par commande, vêtement, opération et client.
- Produire des rapports financiers et analytiques.
- Assurer la traçabilité des ressources utilisées pendant la production.

## 3. Périmètre fonctionnel

Tableau de bord financier · recettes et encaissements · dépenses et décaissements · trésorerie · créances clients · dettes fournisseurs · rémunérations du personnel · comptabilité générale · comptabilité analytique · budgétisation et prévisions · rapports financiers et analytiques · traçabilité des ressources et consommations de production.

## 4. Modules fonctionnels

### 4.1. Tableau de bord financier

Afficher : chiffre d'affaires, recettes encaissées, dépenses engagées, dépenses effectivement payées, solde de trésorerie, créances clients, dettes fournisseurs, coûts de production, marge brute, résultat financier, commandes rentables et non rentables. Les indicateurs sont filtrables par période.

### 4.2. Recettes et encaissements

- Enregistrement d'une recette et association à un client, et à une commande lorsque c'est pertinent.
- Gestion des acomptes, paiements partiels et paiements complets.
- Gestion des échéances et historique des encaissements.
- Édition de reçus.
- Moyens de paiement : espèces, banque, MTN Mobile Money, Orange Money, virement et autres moyens configurables.

**Règle fondamentale :** une facture ou une somme due ne constitue pas automatiquement un encaissement. Les deux opérations sont enregistrées séparément.

### 4.3. Dépenses et décaissements

- Enregistrement et catégorisation des dépenses, association à un fournisseur.
- Association à une commande ou à un centre de coûts lorsque pertinent.
- Ajout de pièces justificatives, paiements partiels, historique des dépenses.
- Catégories indicatives : matières premières, accessoires de couture, salaires et rémunérations, transport, électricité, eau, loyer, entretien et réparation, communication, charges administratives, autres charges.

**Règle :** une dépense engagée et son règlement effectif sont deux événements distincts.

### 4.4. Trésorerie

Gestion de plusieurs comptes : caisse physique, comptes bancaires, MTN Mobile Money, Orange Money et autres comptes configurables. Fonctionnalités : consultation des soldes, entrées et sorties de fonds, transferts entre comptes, rapprochement bancaire, clôture journalière, historique et journal de caisse.

Un transfert entre deux comptes de l'entreprise n'est ni une recette ni une dépense.

### 4.5. Clients et créances

Montants facturés et encaissés, calcul automatique du solde restant dû, suivi des acomptes et échéances, historique des paiements, alertes sur les créances en retard.

### 4.6. Fournisseurs et dettes

Fiches fournisseurs, historique des achats, montants dus et réglés, échéances et suivi des paiements, historique des transactions.

### 4.7. Rémunérations du personnel

Le module utilise les identifiants du module Personnel. Il permet : la définition des modes de rémunération ; le suivi des salaires fixes, des rémunérations à la tâche et par opération ou par vêtement ; le suivi des heures lorsque cette fonction est activée ; le calcul des montants dus ; l'enregistrement des avances et des paiements ; la consultation de l'historique individuel. Les rémunérations prévisionnelles sont distinguées des rémunérations validées et effectivement payées.

## 5. Affectations de production et traçabilité individuelle des ressources

Exigence fonctionnelle majeure : l'application retrace les ressources utilisées pendant la fabrication d'une commande, en identifiant les personnes intervenant dans le processus.

### 5.1. Principe général

Chaque commande peut comporter plusieurs vêtements (veste, chemise, pantalon, etc.). Le patron répartit le travail entre plusieurs employés, chacun identifié par son identifiant unique du module Personnel. La traçabilité s'effectue selon quatre dimensions : **commande**, **vêtement ou pièce**, **opération de production**, **employé responsable ou bénéficiaire**. Le système permet de retrouver les matières affectées, consommées, restituées ou perdues pour chacune de ces dimensions.

### 5.2. Identifiants uniques et numérotation des pièces

- **Client :** identifiant unique, permanent et non réutilisable, créé dans le module Clients et repris dans toutes les commandes et opérations liées.
- **Commande :** numéro unique, référence principale pour retrouver vêtements, opérations, affectations, mouvements de matières, recettes et coûts.
- **Employé :** identifiant unique provenant du module Personnel. Le module Finance/Production ne crée jamais un second identifiant pour le même employé.
- **Vêtement ou pièce :** identifiant unique dans le système, plus un code lisible qui reprend le numéro de commande suivi d'un numéro de séquence. Exemple : CMD-2026-0042-V001, CMD-2026-0042-V002, etc.

Dans une commande de 23 pièces, chaque pièce a son propre code : la veste n° 1 ou la chemise n° 3 restent rattachables au premier coup d'œil à la commande CMD-2026-0042. Si un même employé travaille sur trois vestes issues de commandes différentes, chaque veste reste identifiable séparément, avec son historique de production, de matières et de coûts rattaché à la bonne commande.

Règles complémentaires :

- Le code de pièce figure sur la fiche de production, le bon de travail et, si possible, sur une étiquette ou un ticket accompagnant physiquement la pièce.
- Le numéro de pièce n'est jamais un simple numéro séquentiel global : il indique sa commande d'origine.
- Le lien pièce → commande est aussi conservé en base par une relation explicite, sans dépendre uniquement du texte du code.
- Les identifiants sont immuables. Une correction administrative conserve l'ancien identifiant dans l'historique et ne réutilise jamais un numéro déjà attribué.

### 5.3. Modes d'affectation du travail

**A. Affectation par opération.** Le patron répartit les opérations entre employés (ex. : coupe → employé A, assemblage → B, couture → C, finition → D). Ce modèle peut couvrir plusieurs vêtements d'une même commande.

**B. Affectation par vêtement.** Un vêtement complet est confié à un employé (ex. : veste → A, chemise → B, pantalon → C).

**C. Affectation mixte.** Combinaison des deux (ex. : A coupe la veste et le pantalon ; B réalise entièrement la chemise ; C assemble la veste ; D finit la veste et le pantalon).

Le système n'impose pas un modèle unique par commande. Il permet plusieurs affectations successives sur un même vêtement ou une même opération, avec conservation de l'historique.

### 5.4. Traçabilité des matières premières

Chaque mouvement de matière destiné à la production est associé, selon le contexte, à : l'identifiant de la matière, la quantité et l'unité ; la date et l'heure ; la commande ; le vêtement ou la pièce (code lisible) ; l'opération ; l'employé bénéficiaire ; l'utilisateur ayant enregistré le mouvement ; le statut et un commentaire éventuel. Matières concernées : tissus, doublures, boutons, fermetures, fils, entoilages, élastiques et autres accessoires.

### 5.5. Approvisionnement et affectation des matières aux commandes

Les achats de matières peuvent être de deux types :

- **Achat ciblé :** matières achetées pour une commande déterminée.
- **Achat de stock général :** matières achetées pour constituer ou renouveler le stock de l'atelier, sans commande préalable (achats groupés au marché, achats pour une période).

Dans les deux cas, l'achat alimente le stock. L'achat, la réception et l'entrée en stock n'affectent pas, à eux seuls, le coût d'une commande. Même un achat fait en pensant à une commande précise ne l'impute pas : **l'affectation analytique définitive intervient uniquement à la sortie du magasin, quand la matière est remise à un employé pour une tâche de production.**

À cette sortie, le système enregistre obligatoirement, pour les matières destinées à une production individualisée : la commande, la pièce ou le vêtement, l'opération lorsque pertinente, et l'employé bénéficiaire. Les matières à usage commun ou non encore affectées restent dans le stock général ou dans un emplacement de travail non affecté.

La valorisation de la matière consommée suit la méthode de valorisation du stock configurée. Le système conserve le lien entre l'entrée en stock, la sortie physique, la consommation validée et l'imputation analytique finale. Cela évite d'imputer un coût à la commande à l'achat puis une seconde fois à la consommation.

### 5.6. Modes de gestion des matières : les deux méthodes (BOTH)

**Méthode 1 : remise enregistrée par le patron ou le magasinier.** Sélection de la commande, du vêtement ou de l'opération, de l'employé bénéficiaire, des matières et quantités ; enregistrement de la remise ; génération d'un mouvement de transfert vers l'employé ou son emplacement de travail. L'employé déclare ensuite les quantités consommées, restituées et les chutes réutilisables.

**Méthode 2 : prélèvement ou déclaration directe par l'employé**, lorsque les autorisations le permettent. L'employé s'identifie, sélectionne la commande, le vêtement ou l'opération et les matières, indique les quantités et soumet sa déclaration. Elle ne contourne jamais la validation du responsable de production.

**Règle BOTH :** les deux méthodes coexistent dans le même système. Les autorisations sont configurables par rôle. Un employé ne peut pas valider ses propres déclarations sans permission explicite.

### 5.7. Workflows

**Workflow A : remise par le patron ou le magasinier**

1. Sélection de la commande, du vêtement ou de l'opération, de l'employé, des matières et quantités.
2. Enregistrement de la sortie physique par un utilisateur habilité, puis du transfert dans l'application.
3. Déclaration par l'employé des quantités consommées et restituées.
4. Contrôle et validation par le manager.
5. Mise à jour des stocks, des coûts analytiques et de l'historique (calcul de la perte, voir 5.10).

**Workflow B : prélèvement déclaré par l'employé**

1. Connexion de l'employé, sélection de la commande et de la tâche.
2. Déclaration des matières prélevées (demande ou mouvement de prélèvement selon les droits).
3. Déclaration des quantités consommées et restituées.
4. Transmission, vérification et validation par le manager.
5. Actualisation des stocks et de la comptabilité analytique.

Le système distingue trois événements : la demande de prélèvement, la sortie physique de stock et la consommation effective.

### 5.8. Validation par le manager

La validation est confiée au patron ou au responsable de production ayant le rôle Manager. Le magasinier enregistre les mouvements physiques mais n'a pas automatiquement le droit de valider les consommations. Le manager peut : consulter les déclarations, comparer quantités remises et déclarées, vérifier consommations et restitutions, contrôler les écarts, accepter ou rejeter, demander une correction ou une justification, commenter, consulter l'historique.

Une déclaration rejetée n'est jamais supprimée : elle garde son historique et son motif. Le système enregistre l'identité du manager, la date, l'heure et la décision. Seul un utilisateur ayant explicitement le rôle ou la permission peut valider ; le cumul de fonctions est contrôlé pour éviter une validation sans contrôle indépendant lorsque l'organisation le permet.

### 5.9. Restitutions, chutes et déchets

| Catégorie | Traitement |
| --- | --- |
| Matière consommée | Imputation du coût à la production |
| Matière non utilisée | Restitution au stock |
| Chute réutilisable | Retour dans un stock de matières récupérables (si l'atelier choisit de la déclarer) |
| Écart restant (perte calculée) | Calculé automatiquement, voir 5.10 |

Les chutes réutilisables ne sont pas automatiquement des déchets. Une perte n'est pas automatiquement une faute de l'employé : le système enregistre les faits et laisse le responsable les analyser.

### 5.10. Calcul automatique des pertes

Pour éviter d'alourdir le système, la perte n'est jamais saisie pièce par pièce : elle est **calculée automatiquement** à partir de ce qui a été remis.

```latex
\text{Perte} = \text{quantité remise} - \text{quantité consommée} - \text{quantité restituée}
```

Règles :

- Si l'employé n'a déclaré ni consommation ni restitution, la totalité du solde est une perte.
- L'employé et le manager ne saisissent pas chaque chute ou morceau perdu. Le système permet en revanche une **justification ou un commentaire** lorsqu'un écart paraît anormal.
- Le calcul définitif intervient à la **clôture** de la remise ou de la tâche, une fois consommations et restitutions déclarées et validées par le manager. Avant la clôture, le solde non expliqué apparaît comme « quantité encore à rapprocher » ou « remise en cours », jamais comme une perte définitive.
- Si consommation + restitution dépassent la quantité remise, le système signale une anomalie et bloque la validation normale jusqu'à correction ou justification autorisée.
- Les restitutions partielles et plusieurs déclarations successives pour une même remise sont possibles.
- Avec le temps, le suivi des pertes par employé permet d'identifier les écarts récurrents, de recueillir des explications et d'améliorer l'organisation du travail.

### 5.11. Transfert de matières entre employés

Quand une tâche passe d'un intervenant à un autre (ex. : A coupe, puis transfère les pièces découpées à B pour l'assemblage), le transfert enregistre : l'employé qui remet, celui qui reçoit, la commande, le vêtement ou l'opération, la quantité, la date et l'heure, l'utilisateur ayant enregistré. Un transfert n'est ni une consommation ni une nouvelle acquisition : il préserve la chaîne de garde, et la quantité passe sous la responsabilité du receveur sans être comptée comme perte.

### 5.12. Responsabilité, consommation et coût

Trois notions distinctes : les **matières confiées** à un employé (garde et responsabilité opérationnelle), les **matières consommées** (effectivement utilisées) et le **coût imputé** à l'employé (indicateur analytique). Ce coût n'est jamais automatiquement une retenue sur salaire ni une responsabilité financière personnelle.

### 5.13. Opérations et temps de travail

Chaque tâche enregistre : commande, vêtement (code de pièce), opération, employé affecté, dates de début et de fin, temps passé lorsque suivi, statut, quantité produite ou nombre de pièces traitées, commentaires. Cela permet de calculer la main-d'œuvre par commande, vêtement, opération et employé.

### 5.14. Tableaux de bord analytiques de production

- **Par employé :** matières reçues, transférées, consommées, restituées, pertes calculées et justifiées, valeur consommée, temps de travail, coût de main-d'œuvre, commandes auxquelles il a participé.
- **Par commande :** coût des matières, de la main-d'œuvre et des opérations, coût total de production, marge brute, rentabilité.
- **Par vêtement :** matières utilisées, quantités et coût, employés intervenants, opérations réalisées, coût de fabrication.
- **Par opération :** employés affectés, matières consommées, temps passé, coût, écarts prévisionnel/réel.

### 5.15. Compléments liés aux coûts et aux paiements

- **Reprises.** Une tâche de production peut être marquée « reprise » (retouche après essayage, correction). Son temps et ses matières sont imputés à la commande d'origine et au code de la pièce, et la rentabilité de la commande les fait apparaître séparément.
- **Matière fournie par le client.** Une matière peut être marquée « fournie par le client ». Elle reste traçable (remise, consommation, restitution) mais n'entre jamais dans la valorisation du stock ni dans le coût de revient.
- **Solde et retrait.** Le module lit le statut « prêt / retiré » et la date promise de la commande pour afficher le solde restant dû et déclencher l'alerte de créance. Le taux d'acompte est configurable.
- **Sous-traitance.** Un atelier ou intervenant externe, payé à la tâche, est géré comme un fournisseur ou un intervenant rémunéré (4.6 et 4.7), avec les mêmes règles d'affectation et de traçabilité des pièces.
- **Rémunération à la pièce.** Elle se calcule à partir des tâches validées, avec le code de la pièce comme référence, afin de limiter les contestations lors du paiement.

## 6. Comptabilité générale

Le module permet : la gestion du plan comptable et des journaux, l'enregistrement des écritures, la production d'écritures équilibrées, la consultation du grand livre, la production de la balance et des états financiers, l'archivage des pièces justificatives. Le référentiel cible est le SYSCOHADA applicable au Cameroun ; sa configuration définitive et son usage pour les documents officiels doivent être validés par un professionnel comptable compétent.

Ces écritures sont **générées automatiquement** par la plateforme à partir des opérations saisies (achats, ventes, encaissements, paiements, consommations, rémunérations), selon des règles de correspondance configurées avec le comptable. L'utilisateur n'a jamais à les saisir ni à choisir un compte. Une saisie manuelle d'écriture reste possible, réservée au rôle Comptable, pour les opérations diverses et les corrections.

## 7. Comptabilité analytique

### 7.1. Axes analytiques

Commande, client, vêtement (pièce), opération, employé et centre de coûts.

### 7.2. Coûts directs

Tissu et doublure consommés, boutons et accessoires, fil et fournitures, main-d'œuvre directement affectée.

### 7.3. Coûts indirects

Électricité, loyer, eau, entretien, charges administratives, amortissement des équipements. Ils sont répartis selon des règles configurables.

### 7.4. Coût de revient

Le coût de revient d'une commande intègre : les matières effectivement consommées, la main-d'œuvre, les autres coûts directs et la quote-part des charges indirectes. Le coût des matières suit la méthode de valorisation du stock configurée. Les matières achetées mais non consommées ne sont jamais imputées automatiquement au coût de fabrication, et les matières perdues (calculées selon 5.10) sont valorisées au même coût que les matières consommées.

## 8. Budgétisation et prévisions

Élaboration de budgets, prévisions de recettes et de dépenses, budgets de production, comparaison prévisionnel/réel, analyse des écarts, alertes de dépassement. Les coûts prévisionnels restent distincts des coûts réellement constatés.

## 9. Interactions avec les modules existants

| Module | Interaction avec Finance |
| --- | --- |
| Clients | Facturation, encaissements et créances |
| Commandes | Coûts, recettes et rentabilité |
| Production | Main-d'œuvre, opérations et coûts |
| Stocks | Achats, sorties, transferts, consommations et restitutions |
| Personnel | Identifiants, affectations et rémunérations |
| Utilisateurs | Permissions, validations et journal d'audit |

Le module Finance réutilise les identifiants existants des clients, commandes, vêtements, employés et matières, sans duplication.

## 10. Règles de gestion et automatisations

- Une facture n'est pas un encaissement ; une dépense engagée n'est pas un décaissement.
- Un achat de matière n'est pas une consommation de production ; il alimente le stock, qu'il soit ciblé ou général.
- L'imputation d'une matière à une commande se fait uniquement à la sortie du magasin vers un employé.
- Une sortie vers un employé n'est pas une consommation. Une restitution génère un mouvement de stock approprié.
- Un transfert entre employés ne crée aucune consommation supplémentaire.
- La perte est calculée automatiquement : remis − consommé − restitué, définitive à la clôture de la remise.
- Les matières consommées sont imputées à la commande correspondante ; les opérations individuelles sont associées aux employés concernés.
- Une commande, un vêtement ou une opération peut impliquer plusieurs employés.
- Les consommations partagées peuvent être réparties selon une règle définie ou affectées à une équipe ou à un centre de coûts ; les charges communes non attribuables vont à un centre de coûts.
- Les opérations validées conservent leur historique ; les corrections passent par des opérations correctives tracées, jamais par suppression silencieuse.
- Les coûts sont calculés sans double comptabilisation.

## 11. Modèle de données initial

| Entité | Fonction |
| --- | --- |
| ProductionTask | Tâche ou opération de production |
| TaskAssignment | Affectation d'une tâche à un employé |
| GarmentComponent | Vêtement ou pièce d'une commande (avec code lisible) |
| MaterialIssue | Remise ou sortie de matière (moment de l'affectation à la commande) |
| MaterialIssueLine | Détail des matières remises |
| MaterialConsumption | Déclaration de consommation |
| MaterialReturn | Restitution de matière |
| MaterialLoss | Perte calculée automatiquement à la clôture de la remise, avec justification facultative |
| MaterialTransfer | Transfert entre employés |
| EmployeeWorkLog | Suivi du temps et du travail |
| EmployeeCostAllocation | Affectation analytique des coûts |
| ManagerApproval | Historique des validations |
| CostCenter | Centre de coûts |

Les entités sont reliées aux identifiants des modules existants. Les champs techniques définitifs seront précisés lors de la conception de la base de données.

**Identification et intégrité.** Les entités Client, Commande, Employé et Pièce/Vêtement ont chacune une clé technique unique en base et, si nécessaire, une référence métier lisible. Les références de commande et de pièce sont uniques et non réutilisables. La référence d'une pièce permet de reconnaître sa commande d'origine, tout en conservant une relation de données explicite pièce → commande.

Les mouvements de stock, remises, consommations, restitutions et transferts ont leurs propres identifiants de transaction et les références des objets concernés. Les contraintes d'unicité sont garanties par la base de données, pas seulement par l'interface.

## 12. Utilisateurs, rôles et permissions

| Rôle | Permissions principales |
| --- | --- |
| Patron / Administrateur | Accès global, gestion et validation |
| Manager / Responsable production | Affectation des tâches, contrôle et validation des consommations |
| Magasinier | Gestion physique des stocks, sorties (avec affectation commande, pièce, employé) et restitutions |
| Employé / Couturier | Consultation des tâches, déclaration des matières et du travail |
| Comptable | Gestion comptable, trésorerie et rapports financiers |

Les permissions sont configurables séparément des profils. Un utilisateur ne peut faire que les actions explicitement autorisées.

## 13. Sécurité et intégrité des données

Authentification sécurisée, gestion des permissions, historique des opérations, identification de chaque utilisateur, horodatage des mouvements, journal des modifications, protection contre les suppressions non autorisées, sauvegardes régulières, conservation des pièces justificatives. Les mouvements financiers et de stock validés sont protégés contre toute modification non tracée.

## 14. Exigences non fonctionnelles

La plateforme est simple à utiliser, adaptée aux ordinateurs et aux téléphones, réactive, sécurisée, évolutive, compatible avec les modules existants et capable de conserver un historique détaillé. Les formulaires de déclaration de matières restent assez simples pour être utilisés sans formation informatique approfondie : l'employé ne saisit jamais les pertes.

## 15. Tests d'acceptation

1. **Affectation par opération.** Une commande comporte une veste, une chemise et un pantalon. Le manager affecte coupe, assemblage et finitions à différents employés ; le système conserve les affectations et les relie à la commande.
2. **Affectation par vêtement.** Veste, chemise et pantalon sont confiés à trois employés ; le système calcule le coût de chaque vêtement.
3. **Affectation mixte.** Un employé coupe plusieurs vêtements, d'autres assemblent et finissent ; le système conserve la répartition réelle.
4. **Remise par le magasinier.** 5 mètres de tissu sont remis à un employé ; le système enregistre un transfert, pas une consommation de 5 mètres.
5. **Déclaration de consommation.** L'employé déclare 4 m consommés et 1 m restitué ; après validation du manager, le système enregistre 4 m de consommation et 1 m de restitution, et une perte calculée de 0.
6. **Prélèvement direct.** L'employé déclare un prélèvement pour une commande ; le système enregistre son identité, la commande et le mouvement, puis soumet au manager.
7. **Perte en attente.** Tant que la remise n'est pas clôturée, le solde non expliqué reste « à rapprocher » et n'est pas une perte définitive.
8. **Transfert entre employés.** Un employé transfère du tissu à un autre ; la chaîne de garde est conservée sans nouvelle consommation.
9. **Rentabilité.** Une commande implique plusieurs employés et vêtements ; le système donne le coût total, par vêtement et par employé.
10. **Pas de double comptabilisation.** Achat, transfert au couturier, consommation et restitution des chutes sont des événements distincts ; le coût consommé n'est compté qu'une fois.
11. **Identifiants uniques.** Créer plusieurs clients, commandes, employés et pièces : aucun identifiant ne se répète et chaque pièce affiche une référence qui révèle sa commande (ex. CMD-2026-0042-V003).
12. **Achat de stock général.** Un achat groupé sans commande fait augmenter le stock sans imputation à une commande ; à la sortie vers un employé, la commande et la pièce sont renseignées.
13. **Calcul automatique de la perte.** 5 m remis, 4 m consommés validés, 0,5 m restitué : la perte calculée est de 0,5 m, sans saisie séparée.
14. **Clôture et anomalie.** Un solde non rapproché reste « en cours » tant que la remise n'est pas clôturée ; si consommé + restitué dépasse le remis, le système signale une anomalie et bloque la validation normale.

## 16. Phasage du développement

**Phase 1, fondations financières :** recettes, dépenses, trésorerie, clients et fournisseurs.

**Phase 2, intégration avec la production :** identifiants uniques et numérotation des pièces, affectation des tâches, gestion des employés, achats de stock général, remises, consommations, restitutions, calcul automatique des pertes, validation par le manager.

**Phase 3, comptabilité et analytique :** comptabilité générale, coûts de revient, coûts par employé et par vêtement, rentabilité par commande.

**Phase 4, pilotage :** budgets, prévisions, rapports avancés, tableaux de bord, analyse des performances.

## 17. Indicateurs de réussite

Le module est fonctionnel lorsque l'entreprise peut : connaître sa situation financière à tout moment ; identifier créances et dettes ; suivre recettes et dépenses ; connaître le coût réel de chaque commande et de chaque vêtement ; identifier les employés ayant participé à une commande ; connaître les matières confiées à chaque employé, celles consommées, restituées et les pertes calculées ; mesurer les coûts par opération ; déterminer la rentabilité de chaque commande ; retrouver d'un coup d'œil la commande d'origine d'une pièce grâce à son code.

## 18. Directives à l'équipe de développement

Le module ne doit pas être un système indépendant : il exploite les données des modules Clients, Commandes, Production, Stocks et Personnel. Les développeurs doivent :

- Réutiliser les identifiants existants et éviter toute duplication.
- Garantir l'unicité et l'immuabilité des identifiants client, commande, employé et pièce au niveau de la base de données.
- Séparer les mouvements physiques de stock des consommations réelles, et la trésorerie des opérations comptables.
- N'affecter une matière à une commande qu'à la sortie du magasin, jamais à l'achat.
- Calculer automatiquement les pertes (remis − consommé − restitué) à la clôture, sans saisie manuelle.
- Permettre les affectations par opération, par vêtement ou mixtes, et les deux méthodes de gestion des matières.
- Soumettre consommations et restitutions à la validation du manager, avec historique non destructif des validations et corrections.
- Assurer une traçabilité complète et calculer les coûts sans double comptabilisation.

**Exigence centrale :** à partir d'une commande, le patron retrouve les vêtements concernés, les opérations réalisées, les employés intervenants, les matières remises, consommées et restituées, les pertes calculées et le coût réel de fabrication. Le système assure la continuité entre gestion commerciale, production, stocks et comptabilité.

*Fin du cahier des charges fonctionnel – Version 1.3*
