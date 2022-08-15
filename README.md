# Autenticación y autorización con LDAP

En general, el acceso a la funcionalidad de una aplicación se proporciona de manera restringida a los usuarios. Ciertos usuarios podrán ejecutar ciertas acciones pero no otras. Para saber qué puede o no hacer un usario hay que pasar por dos procesos: autenticación y autorización.

## Autenticación

Este es el proceso de validar que un usario es quien dice ser, y no otro. La forma típica de autenticar a un usuario es mediante nombre de usuario y clave (password). El nombre permite saber si dicho usuario existe, y la clave permite validar que quien está tratando de ingresar en efecto es dicha persona

> Cualquiera puede decir que es "pedro", pero solo el verdadero "pedro" conoce su clave (o al menos así debería ser si "pedro" tomó las medidas necesarias).

Si ambos valores, usuario y clave, coinciden o son correctos, el usuario puede ingresar, caso contrario se rechaza su acceso.

Esto no es más que el primer paso, ya que el usuario que ha sido autenticado, debe estar también autorizado a efectuar la acción que le interesa.

## Autorización

Este es el proceso de validar que un usuario que ya fue autenticado, y por ende tiene acceso al sistema, tiene el permiso necesario para ejecutar una cierta acción. Para esto, atado a cada usuario en el sistema, también se debe encontrar información adicional que le permita a la aplicación saber si el usuario, o más generalmente el "rol" de dicho usuario, le permite hacer lo que pretende.

> A manera de ejemplo sencillo, supongamos que "pedro" accede a una aplicación que potencialmente permite hacer CRUD sobre una tabla en una base de datos. Los administradores de la aplicación pueden haber decidido que todos los usuarios autenticados pueden efectuar un Retrieve, pero que solo los usuarios operadores, o con el rol de operador, pueden efectuar las otras acciones. Una vez "pedro" autenticado, el sistema debió también entregar información de su rol, como por ejemplo: *usr=pedro;rol=empleado*. Si "pedro" intenta hacer un Retrieve debe ser posible, puesto que está autenticado. Sin embargo, si intenta hacer un Create, la aplicación se lo impedirá, ya que no es operador, y por tanto no está autorizado. Si quien se autentica en cambio es "maria", y la información de su rol dice *usr=maria;rol=operador*, el sistema le debe permitir hacer todo, ya que tiene la autorización necesaria.

## Implementación

Incluir las funcionalidades de autenticación y autorización en una aplicación (web) es totalmente posible, y muchas aplicaciones lo hacen. El proceso sería, incluir una pantalla o página de "login" para que el usuario pueda autenticarse con sus credenciales. La aplicación busca en su base de datos si la información coincide o no. Si la autenticación es correcta, el usuario entra al sistema, caso contrario se le presenta nuevamente la página de login, o se presenta una página de error. Por cada acción que el usuario desee ejecutar en la aplicación, esta debe buscar en la base de datos el rol del usuario, y según ello decidir si este tiene el permiso adecuado para hacerlo.

Per-se, este proceso no es complejo. Se debe ser cuidadoso y minucioso a la hora de diseñar y programar esta funcionalidad y listo. Más aún, toda aplicación necesitará algún tipo de acciones dedicadas a ete proceso. Sin embargo, consideremos exclusivamente el caso de los ususarios y los roles en una organización donde además existen varias aplicaciones. Por cada aplicación sería necesario definir una base de datos que guarde la información para el proceso de validación. Esto implica primero que debe haber una coordinación precisa para definir una estructura común para la información, y segundo que debe haber una sincronización oportuna cuando la información cambia (nuevos usuarios, cambio de roles, ...).

Esta coordinación y sincronización entre aplicaciones, se ha visto hasta la saciedad que falla constantemente y que produce graves inconsistencias y fallos en la seguridad de una organización. La alternativa actualmente utilizada para solventar esta problemática implica que la información de usuarios y roles se guarda de manera centralizada, en un solo lugar, y que todas las aplicaciones se comuniquen con este repositorio, de una manera estándar, para todos los procesos de autenticación y autorización. 

## Protocolo ligero de acceso a directorios (LDAP)

El *Lightweight Directory Access Protocol*, [LDAP](https://es.wikipedia.org/wiki/Protocolo_ligero_de_acceso_a_directorios), es un estándar para acceder a la información guardada en un directorio jerárquico y estructurado. Esta información puede constar de usuarios, roles, departamentos y recursos en general, que describen a una organización y a sus componentes.

Estos directorios son similares a una base de datos configurada para realizar búsquedas muy rápidamente y proporcionar sobre todo un acceso de lectura muy eficiente. Los detalles específicos dependerán en cierto sentido de la implementación específica del protocolo, y los proveedores más usados son [OpenLDAP](https://www.openldap.org/) y [Active Directory](https://docs.microsoft.com/en-us/windows-server/identity/ad-ds/get-started/virtual-dc/active-directory-domain-services-overview).

### OpenLDAP

En esta parte vamos a instalar y configurar un servidor LDAP en un equipo Ubuntu.

> Ref: https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-openldap-and-phpldapadmin-on-ubuntu-16-04

> Puede hacerse en cualquier equipo Linux, desktop o server, físico o virtual, pero estas instrucciones utilizan el estilo Debian y están centradas en Ubuntu.

- Como siempre, primero asegúrese que su sistema está actualizado

    ```
    sudo apt update
    ```

- Instale OpenLDAP

    ```
    sudo apt-get install slapd ldap-utils
    ```

    > Seguramente el instalador le habrá pedido ingresar el password para el usuario "admin". En el siguiente paso podrá modificarlo si desea.

Una vez terminada la instalación, es necesario efectuar una configuración inicial antes de ingresar cualquier información en el directorio, esto sobretodo se debe a que el nombre de dominio que se está utilizando para su directorio es "example.com" y en general se prefiere usar el nombre de dominio oficial de su organización. Este nombre de dominio es importante por que hace las veces de raíz del árbol de información, y estará integrado a todos los objetos que cree.

> Si no tiene nombre de dominio oficial (o si solo está haciendo pruebas) no hay realmente problema, puede usar el dominio que usted desee, o dejar example.com (si deja example.com ni siquiera necesitaría hacer una nueva configuración).

- Configure OpenLDAP

    ```
    sudo dpkg-reconfigure slapd
    ```

Al hacer esto deberá responder un par de preguntas:
> Las respuestas específicas Yes/No podrían cambiar de acuerdo a sus necesidades, pero si las cambia averigue bien lo que va a hacer!

- Omit OpenLDAP server configuration? \<No>
- DNS domain name: (por ejemplo "pollos.com")
- Organization name: (por ejemplo "Pollos Refritos")
- Administrator password: (por ejemplo "12345678")
- Confirm password: (debe ser igual al anterior)
- Do you want the database to be removed when slapd is purged? \<No>
- Move old database? \<Yes>

Ahora vamos a inhabilitar el acceso anónimo a ldap

> este paso no es imprescindible, pero es muy recomendable sobretodo si su servidor es de acceso público

- Cree un archivo con extensión *.ldif* e ingrese el siguiente contenido (puede usar [ldap_disallow_anonymous.ldif](ldap_disallow_anonymous.ldif)):

```
dn: cn=config
changetype: modify
add: olcDisallows
olcDisallows: bind_anon

dn: cn=config
changetype: modify
add: olcRequires
olcRequires: authc

dn: olcDatabase={-1}frontend,cn=config
changetype: modify
add: olcRequires
olcRequires: authc
```

- Ejecute esta instrucción:

> Se asume que guardó el archivo en */home/yo/*. Si no es así modifique el path acorde

```
sudo ldapadd -Y EXTERNAL -H ldapi:/// -f /home/yo/ldap_disallow_anonymous.ldif
```

Ahora vamos a habilitar las conexiones seguras porSSL/TLS

- El acceso seguro, ldaps://, se efectua por el puerto 636, que debe abrirse en el firewall.
- Por defecto OpenLDAP no escucha en este puerto, de manera que debe abrir el archivo */etc/default/slapd*, y modificar la línea donde se encuentra la variable SLAPD_SERVICES:

    ```
    SLAPD_SERVICES="ldap:/// ldapi:///"
    ```

    Para añadir "ldaps:///", de manera que quede así:

    ```
    SLAPD_SERVICES="ldap:/// ldaps:/// ldapi:///"
    ```

- Para permitir conecciones seguras es necesario que su servidor LDAP cuente con un certificado. Si su equipo ya ofrecía conexiones seguras, por ejemplo por HTTPS, entonces ya tiene un certificado y solo debe configurar su servidor para reutilizarlo. Si no, hay varias alternativas:
    - Si ya tiene un nombre de dominio propio, puede conseguir un certificado oficial gratuito con [Let's Encrypt](https://letsencrypt.org/)
        - Si no tiene un nombre de dominio propio, pero tiene una IP fija, puede conseguir un dominio gratuitamente en [FreeNom](https://www.freenom.com/)
    - Si su IP es variable, como sucede en una red personal o en su casa, puede conseguir un dominio con [No-Ip](https://www.noip.com/), quienes también le ofrecen un certificado gratuito.
    - Puede generar un certificado "self-signed". Esta opción da problemas con clientes que se conectan desde el internet, ya que estos certificados no se pueden validar, al no ser generados por una autoridad reconocida. Para redes locales, sin embargo, funciona bien. [Aquí tiene una guía](https://github.com/daoc/TLS-certificates).
    - Obviamente tiene muchas alternativas pagadas!

- Una vez con su certificado, debe asegurarse que cuenta con los tres archivos requeridos:
    - El certificado del servidor (supongamos que está en el archivo */home/yo/certs/cert.pem*).
    - La clave privada de dicho certificado (supongamos que está en el archivo */home/yo/certs/privkey.pem*).
    - La cadena completa de certificados de la CA (Certificate Authority) que emitió el certificado (supongamos que está en el archivo */home/yo/certs/fullchain.pem*).

- Es necesario verificar que el usuario **openldap** pueda leer la clave privada. Para esto puede seguir estos pasos:
    - Añada openldap al grupo **ssl-cert**
        ```
        sudo usermod -a -G ssl-cert openldap
        ```
    - Cambie los permisos al archivo
        ```
        sudo chown :ssl-cert /home/yo/certs/privkey.pem
        sudo chmod 640 /home/yo/certs/privkey.pem
        ```

- Cree un archivo con extensión *.ldif* e ingrese el siguiente contenido (puede usar [ldap_enable_tls.ldif](ldap_enable_tls.ldif)):

```
dn: cn=config
changetype: modify
add: olcTLSCACertificateFile
olcTLSCACertificateFile: /home/yo/certs/fullchain.pem
-
add: olcTLSCertificateKeyFile
olcTLSCertificateKeyFile: /home/yo/certs/privkey.pem
-
add: olcTLSCertificateFile
olcTLSCertificateFile: /home/yo/certs/cert.pem
```

- Ejecute esta instrucción:

> Se asume que guardó el archivo en */home/yo/*. Si no es así modifique el path acorde

```
sudo ldapmodify -H ldapi:// -Y EXTERNAL -f /home/yo/ldap_enable_tls.ldif
```

- Reinicie el servicio

    ```
    sudo service slapd restart
    ```

- Cruce los dedos y pruebe alguna consulta simple desde el terminal

    ```
    ldapwhoami -W -D cn=admin,dc=pollos,dc=com -H ldaps://pollos.com
    ```

    El sistema le pedirá su password y si todo va bien responderá:

    ```
    Enter LDAP Password:
    dn:cn=admin,dc=pollos,dc=com
    ```

