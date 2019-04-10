## Welcome to Cisco Addons for GUI Manager 
| [English](README.md) | [Russian](README.ru.md) 

## Link

[![Download Sccp-Mamager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/PhantomVl/sccp_manager)и традиционно добалены новые баги 
[![Download Chan-SCCP channel driver for Asterisk](https://img.shields.io/sourceforge/dt/chan-sccp-b.svg)](https://github.com/chan-sccp/chan-sccp)
[![Chan-SCCP Documentation] (https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)

### История
* Да пока нет ее 

### Кому это надо...
* Это дополнение для телефонов.
* Смотри ветку (https://github.com/PhantomVl/sccp_manager)

### Вжно! В этой ветке лежат самые последне нововведения и обновления, и самые последние БАГИ ! 
    Пользуйся и наслождайся. Так же не забывай писать нам об ошибках, которые ты нашел ! 
    Это очень нам поможет, Я с радостью исправлю то что ты нашел и добалю новых.

### Wiki - Основные Инструкции по настройке 
* тоже пока отсутствует

### Prerequisites - как говориться все, что хуже этого возможно работать тоже будет .... но только вопрос как ?
* Прежде всего нужно настоить mysql, chan_sccp, sccp_manager.

### Installation Очень короткая инструкция

- Установка модуля
>     cd /var/www/html/cisco/
>     git clone https://github.com/PhantomVl/sccp_addons.git
    
- Настройка модуля
    1. Открываем "SCCP Connectivity" -> "Server Config" -> User Rouming -> On
    2. Переходим на закладку "SCCP Device" -> Phone Service URL -> http://[You PBX]/cisco/service.php
    3. Жмем "Сохранить" 
    4. Открываем "SCCP Phone Manager" -> У нас появилась иконка "велосипедик"
    5. А далее, интуитивно понятный интерфейс.

- Обновление модуля
>     cd /var/www/html/admin/modules/sccp_manager/
>     git fetch
>     git pull
>     git checkout develop
