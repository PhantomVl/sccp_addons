## Welcome to Cisco Addons for GUI Manager 
| [English](README.md) | [Russian](README.ru.md) 

## Link

[![Sccp-Mamager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/PhantomVl/sccp_manager)
[![Chan-SCCP channel driver for Asterisk](https://img.shields.io/sourceforge/dt/chan-sccp-b.svg)](https://github.com/chan-sccp/chan-sccp/)
[![Chan-SCCP Documentation] (https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)


### 

### Prerequisites
* First you need to insist mysql, chan_sccp, sccp_manager.


### Installation 

>     cd /var/www/html/cisco/
>     git clone https://github.com/PhantomVl/sccp_addons.git
    
- Module setting
     1. Open "SCCP Connectivity" -> "Server Config" -> User Rouming -> On
     2. Go to the "SCCP Device" tab -> Phone Service URL -> http: // [You PBX] /cisco/service.php
     3. Click "Save"
     4. Open "SCCP Phone Manager" -> We have a "bike" icon
     5. And next, an intuitive user interface.

- Update
>     cd /var/www/html/admin/modules/sccp_manager/
>     git fetch
>     git pull
>     git checkout develop
