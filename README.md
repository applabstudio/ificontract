# Webit Drupal 8 (base) Installation Profile.
[produzione / webit-installer ](https://gitlab.infotel.it/produzione/webit-installer.git)

This project manages the site dependencies with [Composer](https://getcomposer.org/).

For documentation refer to its original source template: [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)


### Configurazione ambiente di sviluppo (Siteground)

__Riferimenti utili__  
Accesso cPanel: https://siteground-01.webit.it:2083/  

Accesso ssh: $ ssh -p 18765 [UTENTE]@siteground-01.webit.it 


Un utente si deve occupare di:  

- creare il repository git su gitlab.infotel.it, nel gruppo produzione. N.B. L'utente deve avere privilegi di Owner  
- allestire l'ambiente su siteground 
- configurare i drush alias con i riferimenti degli ambienti di sviluppo (editare file /drush/site-aliases/[folder_name].aliases.drushrc.php)  


N.B. Gli altri utenti che partecipano al progetto devono partire a sviluppare sullo stesso database impostato dall'utente che ha allestito il progetto per avere gli stessi UUID nelle configurazioni.  


### Installazione

Digitare i seguenti comandi da terminal/shell:  

__Clone del profilo di installazione webit__  
$ git clone git@gitlab.infotel.it:produzione/webit-installer.git "nome_cartella"   
$ cd "nome_cartella"    

__Collegamento a repository GIT__ (solo chi allestisce il progetto)  
$ sudo rm -R .git  
$ git init  
$ git remote add origin git@gitlab.infotel.it:produzione/[nome_progetto].git  
$ cd .git/  
$ nano config (filemode=false)  

__Pulizia cartella configurazioni e script bash__ (solo chi allestisce il progetto)  
$ rm config/sync/*  
$ rm profile_config.sh  

__Creazione branch master__ (solo chi allestisce il progetto)    
$ git add .  
$ git commit -m 'first commit'  
$ git push origin master  

__Installazione dipendenze__  
$ composer install  


Una volta completate queste operazioni:

__Impostazione Virtual Host__ (esempio)  
$ cd /etc/apache2/sites-available/  
$ sudo cp www.drupal8-webit.com.conf www.primomigliostartup.com.conf  
$ sudo nano www.primomigliostartup.com.conf  
$ sudo nano /etc/hosts (modifica vhost puntamento cartella web/)

Esempio virtualhost
```
<VirtualHost *:80>  
DocumentRoot "/var/www/htdocs/viedidante/web"  
ServerName viedidante.local  
DirectoryIndex index.html index.htm index.php  
<Directory "/var/www/htdocs">  
AllowOverride All  
</Directory>  
ErrorLog "/var/log/apache2/error_viedidante_log"  
CustomLog "/var/log/apache2/access_viedidante_log" combined  
</VirtualHost>  
```  
$ sudo a2ensite www.primomigliostartup.com.conf  
$ service apache2 reload  

__Creazione database__  
Creare un database vuoto con relativo utente con privilegi di accesso  

Procedere con l'installazione di Drupal da web dall'indirizzo settato nel Virtual host.   
Scegliere come profilo di installazione Webit installer (selezionato automaticamente perchè settato come distribuzione), inserire i dati del database creato.  


__Operazioni preliminari alla creazione dello staging__ 

- aggiornare drush alias 
- settaggio config_exclude_modules in settings.local  
    $settings['config_exclude_modules'] = ['devel','devel_generate','webform_devel','twig_xdebug'];  
- (opzionale) installazione eventuali lingue aggiuntive  
    - settaggio lingua default  
    - impostazione criteri rilevazione lingua (Rilevamento della lingua Contenuto = URL)    
- esportazione configurazione drush cex 
- creazione branch Es. develop, features/#1_frontend, features/#2_backend  


### Ambiente di Staging e Produzione

- procedere con il download del repository (git clone) + installazione dipendenze (composer install)
- importare il database esportato dall'ambiente locale + creazione utente con accesso al db
- editare il file settings.local.php: 
``` 
    $config['system.logging']['error_level'] = 'hide';  
    $config['system.performance']['css']['preprocess'] = TRUE;  
    $config['system.performance']['js']['preprocess'] = TRUE;  
```      
   - commentare eventuali configurazioni con valore 'cache.backend.null'
- rimuovere dal development.services.local.yml il debug per twig e caricare via FTP
- disattivare i moduli per lo sviluppo con config_exclude_modules Es. --> drush pmu devel devel_generate webform_devel twig_xdebug -y
- verificare lo scheduling del dump del database
- attivare il sistema di caching (/admin/config/development/performance)


__Configurazione Memcache__

- abilitare moduli memcache, memcache_admin, webit_memcache
- attivare memcache da cPanel Siteground
- nel file settings.local.php (attenzione alla porta fornita da Siteground) 
```
$settings['memcache']['servers'] = ['127.0.0.1:20001' => 'default'];
$settings['memcache']['bins'] = ['default' => 'default'];
$settings['memcache']['key_prefix'] = '';
$settings['cache']['default'] = 'cache.backend.memcache';
$settings['cache']['bins']['render'] = 'cache.backend.memcache';
```    


### Configurazione Drupal ricorrenti

- Importare il file di traduzione it.po presente nella cartella translation della distribuzione. Creare la cartella translations se non esiste sotto il percorso /web/sites/default/files/
- Settare i permessi di accesso per ogni content type  
- Verificare i permessi sulle tassonomie forniti dal modulo Taxonomy access fix + accesso alla pagina della tassonomia 

...


Enjoy! ... and good work.


@Credits: 
**[Webit](http://www.webit.it)**  
Infotel Telematica S.r.l.
Via Circonvallazione Nuova 57/C
Rimini (Italy)
Email: [info@webit.it](info@webit.it)