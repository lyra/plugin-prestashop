<?php
#####################################################################################################
#
#					Module pour la plateforme de paiement PayZen
#						Version : 1.4f (révision 46408)
#									########################
#					Développé pour Prestashop
#						Version : 1.4.0.x
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						22/04/2013
#						Contact : support@payzen.eu
#
#####################################################################################################

global $_MODULE;
$_MODULE = array();
$_MODULE['<{payzen}prestashop>order_payzen_66c7776255839a5c31403ccac659f56f'] = 'Payer avec PayZen';
$_MODULE['<{payzen}prestashop>order_payzen_ea613ffe86a1bcbcd4c56d802fa0ff0f'] = 'Cliquez ici pour payer avec PayZen';
$_MODULE['<{payzen}prestashop>order_payzen_8adffe14ac469d6a755e7232ba41ec36'] = 'Payer avec PayZen';
$_MODULE['<{payzen}prestashop>order_payzen_bfd7aff6e63af5d91a173f8e52c24c5d'] = 'Cliquez ici pour payer avec PayZen';
$_MODULE['<{payzen}prestashop>order_payzen_577b038d05f71fbc0d8d889d7bf2b77b'] = 'Payer avec PayZen';
$_MODULE['<{payzen}prestashop>order_payzen_aa955a0e00bf5b9bdad775dbab47e84c'] = 'Cliquez ici pour payer avec PayZen';
$_MODULE['<{payzen}prestashop>payment_return_93ec33bd6d2df2fd2c69cc83ef77ddee'] = 'La validation automatique n\'a pas fonctionné. Avez-vous configuré correctement l\'URL serveur dans l\'outil de gestion de caisse de votre boutique ?';
$_MODULE['<{payzen}prestashop>payment_return_aed9aa264932d1a9f52d263956117993'] = 'Afin de comprendre la problématique, reportez vous à la documentation du module : ';
$_MODULE['<{payzen}prestashop>payment_return_c3de8b7cfd6b9376c9d13728e4dfa34d'] = '- Chapitre \"A lire attentivement avant d\'aller loin\"';
$_MODULE['<{payzen}prestashop>payment_return_7966c90cf7a27e78417af0bac341923c'] = '- Chapitre \"Paramétrage de l\'URL serveur\"';
$_MODULE['<{payzen}prestashop>payment_return_18c51ad1b1fe562479120ab35f890fc9'] = 'Si vous pensez qu\'il s\'agit d\'une erreur, vous pouvez contacter notre';
$_MODULE['<{payzen}prestashop>payment_return_64430ad2835be8ad60c59e7d44e4b0b1'] = 'service client';
$_MODULE['<{payzen}prestashop>payment_return_73f642dd4a7e09c7570833f584f77263'] = 'PASSER EN PRODUCTION';
$_MODULE['<{payzen}prestashop>payment_return_fce9858aa1e2d1353476b18320719dc3'] = 'Vous souhaitez savoir comment passer votre boutique en production merci de consulter cette URL : ';
$_MODULE['<{payzen}prestashop>payment_return_de24df15226a5139eb60c3b24c1efbd6'] = 'Votre commande a été enregistrée avec une erreur de paiement.';
$_MODULE['<{payzen}prestashop>payment_return_3fee1227f1b7e441476ccb45278a5f22'] = 'Nous vous invitons à contacter notre';
$_MODULE['<{payzen}prestashop>payment_return_2e2117b7c81aa9ea6931641ea2c6499f'] = 'Votre commande sur';
$_MODULE['<{payzen}prestashop>payment_return_75fbf512d744977d62599cc3f0ae2bb4'] = 'est terminée.';
$_MODULE['<{payzen}prestashop>payment_return_ee9d464a5f04b1c5f548d1655691ce82'] = 'Nous avons enregistré votre paiement de';
$_MODULE['<{payzen}prestashop>payment_return_0db71da7150c27142eef9d22b843b4a9'] = 'Pour toute question ou information complémentaire, veuillez contacter notre';
$_MODULE['<{payzen}prestashop>redirect_a40cab5994f36d4c48103a22ca082e8f'] = 'Votre panier';
$_MODULE['<{payzen}prestashop>redirect_31682b69de73c081c487b0cb5002549d'] = 'Redirection vers la plateforme de paiement';
$_MODULE['<{payzen}prestashop>redirect_879f6b8877752685a966564d072f498f'] = 'Votre panier est vide.';
$_MODULE['<{payzen}prestashop>redirect_e555610477d1aa7807d93f28ba80141e'] = 'Paiement par carte bancaire';
$_MODULE['<{payzen}prestashop>redirect_ff1b91552dca022519140532b2b2ab82'] = 'Merci de patienter, vous allez êtes redirigé vers la plateforme de paiement.';
$_MODULE['<{payzen}prestashop>redirect_4ac5858c2ddee9ac2370a6045860620e'] = 'Si vous n\'êtes pas redirigé dans 10 secondes, veuillez cliquer sur le bouton ci-dessous.';
$_MODULE['<{payzen}prestashop>redirect_99938b17c91170dfb0c2f3f8bc9f2a85'] = 'Payer';
$_MODULE['<{payzen}prestashop>payzen_085e103d28457c03842d5bbbafb83f21'] = 'PayZen';
$_MODULE['<{payzen}prestashop>payzen_d3d27364e758bb0ca92b41edaef08de2'] = 'PayZen';
$_MODULE['<{payzen}prestashop>payzen_dc5a1f36bb02b05d0df0e790d451f043'] = 'PayZen';
$_MODULE['<{payzen}prestashop>payzen_9716ac996d516c06b5f7df7281a54e81'] = 'Payer par carte bancaire avec PayZen';
$_MODULE['<{payzen}prestashop>payzen_9086bed79ad3ba231b519fd89f226ff8'] = 'Payer par carte bancaire avec PayZen';
$_MODULE['<{payzen}prestashop>payzen_5228401f52c431c456a73f7d39ca6b16'] = 'Payer par carte bancaire avec PayZen';
$_MODULE['<{payzen}prestashop>payzen_746bb4d715d73e3619f797a7756096e3'] = 'Valeur invalide';
$_MODULE['<{payzen}prestashop>payzen_b0023a15df02ae884e2de275866e389f'] = 'pour le champ :';
$_MODULE['<{payzen}prestashop>payzen_7f319c6b6e79fe8b9b159f569be552ad'] = 'Un problème est survenu lors de la sauvegarde du champ :';
$_MODULE['<{payzen}prestashop>payzen_c888438d14855d7d96a2724ee9c306bd'] = 'Configuration mise à jour';
$_MODULE['<{payzen}prestashop>payzen_c9cc8cce247e49bae79f15173ce97354'] = 'Sauvegarder';
$_MODULE['<{payzen}prestashop>payzen_004a093d1ab4ca81b4024639b4b2427a'] = 'Développé par';
$_MODULE['<{payzen}prestashop>payzen_01ed8a4015b45aa03653c53de46e37ee'] = 'Courriel de contact';
$_MODULE['<{payzen}prestashop>payzen_b1c1d84a65180d5912b2dee38a48d6b5'] = 'Version du module';
$_MODULE['<{payzen}prestashop>payzen_666809109472d77b71fc9930436c7ff1'] = 'Version de la plateforme';
$_MODULE['<{payzen}prestashop>payzen_66b5f3034a6f89c316df97eab1ec5663'] = 'Testé avec prestashop';
$_MODULE['<{payzen}prestashop>payzen_ea3a04528017f1c91975358078ff71e8'] = 'Accès à la plateforme';
$_MODULE['<{payzen}prestashop>payzen_eff08814984e50d7991932fe8bf15991'] = 'Identifiant de votre site';
$_MODULE['<{payzen}prestashop>payzen_049701ca27fd91a0370ff4c41a5dd3f1'] = 'L\'identifiant de votre site, disponible dans l\'outil de gestion de caisse';
$_MODULE['<{payzen}prestashop>payzen_5abbd73fc5189212c57adfb43adac459'] = 'Certificat en mode test';
$_MODULE['<{payzen}prestashop>payzen_51cc994ce3933ec14767f203e5fd6510'] = 'Certificat fourni par la plateforme de paiement';
$_MODULE['<{payzen}prestashop>payzen_8d79d82e7b1b6174ac9a55a760d64edc'] = 'Certificat en mode production';
$_MODULE['<{payzen}prestashop>payzen_0cbc6611f5540bd0809a388dc95a615b'] = 'Test';
$_MODULE['<{payzen}prestashop>payzen_756d97bb256b8580d4d71ee0c547804e'] = 'Production';
$_MODULE['<{payzen}prestashop>payzen_650be61892bf690026089544abbd9d26'] = 'Mode';
$_MODULE['<{payzen}prestashop>payzen_ec56a33e1d251a9f08a7a996feadf4fa'] = 'Mode test ou production';
$_MODULE['<{payzen}prestashop>payzen_fc7208a42c8de03193b3b54912529f48'] = 'Url de la plateforme';
$_MODULE['<{payzen}prestashop>payzen_0abd69106110b238106244caf6701ab3'] = 'Le client sera redirigé à cette adresse pour payer';
$_MODULE['<{payzen}prestashop>payzen_d35acbb07d2841712a937d5748e9bdc2'] = 'Page de paiement';
$_MODULE['<{payzen}prestashop>payzen_86bc3115eb4e9873ac96904a4a68e19e'] = 'Allemand';
$_MODULE['<{payzen}prestashop>payzen_78463a384a5aa4fad5fa73e2f506ecfc'] = 'Anglais';
$_MODULE['<{payzen}prestashop>payzen_cb5480c32e71778852b08ae1e8712775'] = 'Espagnol';
$_MODULE['<{payzen}prestashop>payzen_ad225f707802ba118c22987186dd38e8'] = 'Français';
$_MODULE['<{payzen}prestashop>payzen_4be8e06d27bca7e1828f2fa9a49ca985'] = 'Italien';
$_MODULE['<{payzen}prestashop>payzen_f32ced6a9ba164c4b3c047fd1d7c882e'] = 'Japonais';
$_MODULE['<{payzen}prestashop>payzen_3b261136e3c33f35e0a58611b1f344cb'] = 'Chinois';
$_MODULE['<{payzen}prestashop>payzen_30e32c7c4cf434e9c75e60c14c442541'] = 'Portugais';
$_MODULE['<{payzen}prestashop>payzen_68bf367e228f45ba83cb8831a5ee6447'] = 'Néerlandais';
$_MODULE['<{payzen}prestashop>payzen_c96a77fb323a41898c3b6941a58dc741'] = 'Langue par défaut';
$_MODULE['<{payzen}prestashop>payzen_e27b4ff6694b9ba58627fee38486a7f6'] = 'Langue par défaut de la page de paiement';
$_MODULE['<{payzen}prestashop>payzen_4e1b0d5f96251571c00165c066b7c550'] = 'Langues disponibles';
$_MODULE['<{payzen}prestashop>payzen_e93c33bd1341ab74195430daeb63db13'] = 'Nom de la boutique';
$_MODULE['<{payzen}prestashop>payzen_92e5b9360dff0d8fa8a7762359257fe7'] = 'Nom affiché sur la page de paiement. Laisser vide pour utiliser le nom enregistré par la plateforme.';
$_MODULE['<{payzen}prestashop>payzen_6c61ebc45acfe2f244e91ac4cd423e81'] = 'Url de la boutique';
$_MODULE['<{payzen}prestashop>payzen_b0bf2e6fe7c69c329580221efaa6b2a4'] = 'Url affichée sur la page de paiement. Laisser vide pour utiliser l\'url enregistrée par la plateforme';
$_MODULE['<{payzen}prestashop>payzen_8f497c1a3d15af9e0c215019f26b887d'] = 'Délai';
$_MODULE['<{payzen}prestashop>payzen_123fc485e1ced719eed781646ffe2650'] = 'Délai avant remise en banque (en jours)';
$_MODULE['<{payzen}prestashop>payzen_7a1920d61156abc05a60135aefe8bc67'] = 'Configuration outil de gestion de caisse';
$_MODULE['<{payzen}prestashop>payzen_086247a9b57fde6eefee2a0c4752242d'] = 'Automatique';
$_MODULE['<{payzen}prestashop>payzen_e1ba155a9f2e8c3be94020eef32a0301'] = 'Manuelle';
$_MODULE['<{payzen}prestashop>payzen_31c46d040ddbf15207ebd7b78af8c45d'] = 'Validation du paiement';
$_MODULE['<{payzen}prestashop>payzen_ebd79cb5c4c42bb7d69fb11602e560d8'] = 'En mode manuel, vous devrez confirmer les paiements dans l\'outil de gestion de caisse';
$_MODULE['<{payzen}prestashop>payzen_b1c94ca2fbc3e78fc30069c8d0f01680'] = 'Toutes';
$_MODULE['<{payzen}prestashop>payzen_169a957a5a206a68dfc3fcd3949813c9'] = 'American express';
$_MODULE['<{payzen}prestashop>payzen_834cb54b61bbed9d4296c995e4c5d8b2'] = 'Carte bleue';
$_MODULE['<{payzen}prestashop>payzen_b48167465ffc278e8096a57fb3e5cf24'] = 'Mastercard';
$_MODULE['<{payzen}prestashop>payzen_89fc0d6fe12b0e0c1af5c7a0373435a6'] = 'Visa';
$_MODULE['<{payzen}prestashop>payzen_b0c3cae935975999a5f8ca3062969346'] = 'Cartes disponibles';
$_MODULE['<{payzen}prestashop>payzen_41864fd226b67a722fed1ec2ac72bdcf'] = 'Ne rien sélectionner pour utiliser la configuration de la plateforme.';
$_MODULE['<{payzen}prestashop>payzen_920e5afd43a2aefa56e7b336fe49c5d4'] = 'Montant minimum pour lequel activer 3DS';
$_MODULE['<{payzen}prestashop>payzen_5c0fb3c82d44d4295b5a4c7900aa3e9b'] = 'Seulement si vous avez souscrit à l\'option 3-D Secure sélectif';
$_MODULE['<{payzen}prestashop>payzen_c265395c1d11264e54110007e720db2f'] = 'Restrictions sur le montant';
$_MODULE['<{payzen}prestashop>payzen_9f6e99bdd4184b83dc478d0ab1b4cbf7'] = 'Montant minimum';
$_MODULE['<{payzen}prestashop>payzen_dcd700acd4c6727dca97f5b414cfb384'] = 'Montant maximum';
$_MODULE['<{payzen}prestashop>payzen_9bfa2332c9c7198ac9774c6710bf76c4'] = 'Retour à la boutique';
$_MODULE['<{payzen}prestashop>payzen_b9f5c797ebbf55adccdd8539a65a0241'] = 'Désactivée';
$_MODULE['<{payzen}prestashop>payzen_00d23a76e43b46dae9ec7aa9dcbebb32'] = 'Activée';
$_MODULE['<{payzen}prestashop>payzen_aadb7680e9cf237017cd8b67f6c73260'] = 'Redirection automatique';
$_MODULE['<{payzen}prestashop>payzen_2051113e1d11164763db9463db4f3f78'] = 'Rediriger le client vers la boutique à la fin du processus de paiement';
$_MODULE['<{payzen}prestashop>payzen_e5e1991ad7aaf9fe6464c969920f097a'] = 'Délai en cas de succès';
$_MODULE['<{payzen}prestashop>payzen_46ac211030d7d389966175ae9fd711f4'] = 'Temps en secondes avant que le client soit redirigé vers la boutique après un paiement réussi';
$_MODULE['<{payzen}prestashop>payzen_eb348dd0d56400bd18e7c9e8d25cc666'] = 'Message avant redirection en cas de succès';
$_MODULE['<{payzen}prestashop>payzen_c739fa67a340c76b8bac9281b456ca3e'] = 'Message affiché au client avant qu\'il soit redirigé vers la boutique après un paiement réussi';
$_MODULE['<{payzen}prestashop>payzen_927bb2451d920fdba0ab0823fa7bae6d'] = 'Délai en cas d\'échec';
$_MODULE['<{payzen}prestashop>payzen_78d4bee1d6875f229d4e4320a6abf10e'] = 'Temps en secondes avant que le client soit redirigé vers la boutique après l\'échec du paiement';
$_MODULE['<{payzen}prestashop>payzen_00b7bcd80d9c9f19186b0c729f6085c4'] = 'Message avant redirection en cas d\'échec';
$_MODULE['<{payzen}prestashop>payzen_a4e63e0b942643e02daa5d2d3df9a814'] = 'Message affiché au client avant qu\'il soit redirigé vers la boutique après l\'échec du paiement';
$_MODULE['<{payzen}prestashop>payzen_a6d013c4e4b31bf944c6ac04b3c3d4ea'] = 'GET (paramètres dans l\'url)';
$_MODULE['<{payzen}prestashop>payzen_13b89b1404ec947deeb347c8e3901f9b'] = 'POST (formulaire)';
$_MODULE['<{payzen}prestashop>payzen_5c6a16d0f0782adac84a09b281878e84'] = 'Mode de retour';
$_MODULE['<{payzen}prestashop>payzen_369a63dd2214318d4b7107a0b74cc81f'] = 'Façon dont le client transmettra le résultat du paiement lors de son retour sur la boutique';
$_MODULE['<{payzen}prestashop>payzen_4dcaea1b2d8f025a39f1dec679a6186e'] = 'Retourner au choix du moyen de paiement';
$_MODULE['<{payzen}prestashop>payzen_4d48c7d2809edea0b654f236d9695943'] = 'Enregistrer la commande échouée et retourner à l\'historique';
$_MODULE['<{payzen}prestashop>payzen_0922629c59c5f69e205a4c831f819794'] = 'Gestion des paiements échoués';
$_MODULE['<{payzen}prestashop>payzen_2ec6d9d74ab7d1ef252b9c514c1f0ec8'] = 'Comment traiter le retour du client après que le paiement ait échoué';
$_MODULE['<{payzen}prestashop>payzen_50aeba62672dfa15fed779c0248653cb'] = 'Paramètres GET additionnels';
$_MODULE['<{payzen}prestashop>payzen_2199f134936fe298d2d4e0927211a421'] = 'Paramètres supplémentaires envoyés au retour en mode GET';
$_MODULE['<{payzen}prestashop>payzen_9fe7d4433879f84a21f1a9b47c792550'] = 'Paramètres POST additionnels';
$_MODULE['<{payzen}prestashop>payzen_1a01ef92951bf4fcc5cc02ec502e3e0d'] = 'Paramètres supplémentaires envoyés au retour en mode POST';
$_MODULE['<{payzen}prestashop>payzen_5965e0beeeba5fb79b12a1cff58542da'] = 'Url de retour à la boutique';
$_MODULE['<{payzen}prestashop>payzen_e2bd8487b5a5b58d4c3a0db4e2cc1a0c'] = 'Url vers laquelle le client sera redirigé à la fin du processus de paiement';
$_MODULE['<{payzen}prestashop>payzen_310a02dd69e5109fdec011483776b452'] = 'Url serveur à copier dans l\'outil de gestion de caisse';
$_MODULE['<{payzen}prestashop>unknown_currency_fd817c793427fb36bcffd5e3cb0dd5dc'] = 'Méthode de paiement indisponible pour la devise :';