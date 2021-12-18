# Logbook Spezifikation
## Sinn und Zweck
In der Lebensgeschichte eines Fahrzeugs und seiner Teile gibt es eine lange Liste an Ursachen, die die Gesamtheit eines Fahrzeugs gewollt oder ungewollt verändern und denen mit geeigneten Massnahmen begegnet werden muß. Diese Usachen und Massnahmen gilt es so zu verfolgen, dass die notwendigen Maßnahmen angestoßen werden und der Verlauf jederzeit nachvollziehbar ist.


## Das Konzept
Es gilt, einen pratikablen Kompromiss zwischen zwei kontroversen Forderungen zu finden:
* Zum einen muß die Datenbank alle irgendwie interessanten Daten beinhalten und darstellen können,
* aber auch so simpel sein, dass auch Anfänger möglichst intuitiv damit arbeiten können.

Die DB soll eine Vielzahl an möglichen Ereignissen unterstützen. Ein paar Beispiele dafür wären:
* Ein neues Teil soll eingebaut werden (Steuerung der Produktion)
* Ein aufgetretendes Problem soll behoben werden
* Dem Fahrzeug wird eine Rückrufkation mit Teiletausch zugeordnet
* Ein Service- Intervall löst einen Kühlwasserwechsel aus
* usw.



## Das technische Konzept
Um diese Vielzahl von scheinbar völlig unterschiedlichlichen Anforderungen bedienen zu können, werden einige konzeptionelle Annahmen getroffen:
* Ein Fahrzeug besteht aus vielen Bauteilen (und ist im System auch nur ein Bauteil)
* Jedes Bauteil kann in ein übergeordnetes Bauteil eingebaut sein, d.h. seine Eigenschaft "Parent" kann auf ein anderes Bauteil verweisen. Das oberste Bauteil einer solchen Kette (Teilebaum) ist dann das Fahrzeug 
* Jedes Bauteil hat natürlich eine Teilenummer, aber auch wo möglich eine Seriennumer. Es wird dadurch individuell mit "persönlicher" Lebensgeschichte
* Jedes Bauteil hat eine Menge (programmiertechnisch ein (geschachteltes) Dictonary) an Eigenschaften wie z.B. Fahrzeugausstattung, Fahrgestellnummer, Kundenandresse usw. und eine Menge an Parametern (Aufenthaltsort, Kilometerstand, Füllmengen usw.). Der Unterschied zwischen Eigenschaften und Parametern liegt darin, dass die Eigenschaften als solches statisch sind und nur durch Benutzereingaben verändert werden, während die Parameter sich dynamisch durch äussere Einflüsse verändern und den aktuellen Ist- Zustand wiedergeben.

### Anforderung und Maßnahmen
Um ein möglichst breites Spektrum an Fällen abzudecken, sind alle Abläufe generalisert zweigeteilt in eine (erstmal unerfüllte) **Anforderung** und eine oder mehrere **Maßnahmen**, welche die Anforderung letztlich erfüllen. Ob die durchgeführten Maßnahmen letztlich die Anforderung erfüllen, entscheidet nicht das System, sondern der Benutzer selber.

### Flexibilität durch eingebettete Daten- "Blobs"
Da es nahezu unmöglich (und wohl schwachsinnig) wäre, alle möglichen zu erwartenen Anwendungswünsche hardcoded in die Eingabemasken zu pressen, wird statt dessen folgendes Prinzip angewandt:
* Jede Anforderung und Maßnahme beinhaltet hardocded nur die Felder, die zur eigenen Verwaltung notwendig sind
* Jede Anforderung und Maßnahme besteht aus einem Hilfetext (Markdown), einem Datenblob (JSON), der die Eingaben speichert und einem JSON- Schema (text), welches die Eingaben definiert und verifiziert
* Der Benutzer bekommt einen JSON- Editor (https://github.com/json-editor/json-editor) angezeigt, um die durch das jeweilige Schema erlaubten Eingaben spezifisch für die jeweilige Anforderung/Maßnahme machen zu können.

## Workzones, Rollen und User
* Workzones stellen die unterschiedlichen Verwaltungsbereiche da, damit z.B. die Kleinwagensparte nicht mit den Problemen der Kipplaster behelligt wird
* Rollen: Dies sind die unterschiedlichen Geschäftsbereiche pro Workzone, also z.B. Einkauf, Entwicklung, Qualität; definiert als jene, die bei einer Maßnahme konkret was zu tun haben.
* User werden über das Active Directory authentifiziert
* Jedem User können beliebig viele Kombinationen aus Workzone und Rolle zugeordnet werden, anhand die für ihn aktuellen Aufgaben abgeleitet werden können

## Logging
Jede Aktion (wer ändert was in welcher Form) wird in einem Log protokolliert

## GUI
* Die GUI ist ein Web- Interface
* Referenzbrowser ist ein aktueller Google Chrome.
* Schwierig, aber durchaus erstrebenswert ware ein repositive Design, um auch vor Ort am Fahrzeug damit arbeiten zu können.
* Eine Browser- basierte Standorterkennung wäre praktisch, um alle Fahrzeuge in unmittelbarer Nähe (z.B. auf einem Betriebshof) identifizieren zu können, an denen noch Arbeiten anstehen
* Ein User kann alle Anforderungen und Maßnahmen der Workzones einsehen, die in seinen Rollen benannt sind.
* Die Bauteile- Ansicht dagegen ist global, um z.B. bei Qualitätsproblemen alle Teile finden zu können, die irgendwo verwendet wurden.

### Todo
Der Startbildschirm zeigt alle offenen Anforderungen und Maßnahmen der Rollen des Anwenders. Schwierig, aber reizvoll wäre hier eine Suchfunktion, die ihm nur die Elemente anzeigt, die sich innerhalb eines bestimmten Entfernung- Radius befinden. Von hier kann er in die einzelnen Ansichten weiterverzweigen.

### Bauteil
Durch Querverweise oder direkte Suche nach der Teilenummer kommt man auf die Bauteil- Seite. Sie enthält die Eigenschaften und Parameter des Bauteils und die offenen/geschlossenen Anforderungen und Maßnahmen sowohl für das Bauteil selber als auch für die Kinder- Bauteile  Hier können alle Elemente editiert oder neue Anforderungen ausgelöst werden.

### Anforderungen & Maßnahmen
* Anforderungen sind in der Datenbank vordefiniert (Templates) und können einem Bauteil zugeordnet werden.
* Um dem Anwender eine Hilfestellung zu geben, sind die ebenfalls in der Datenbank vordefinierten Maßnahmen (Templates) optional auch schon verschiedenen Anforderungen zugeordnet.
* Dies ist aber auch nur eine Hilfestellung, kein Muss, der Anwender kann jederzeit Maßnahmen zu einer Anforderung hinzufügen oder entfernen
* Sobald der Anwender eine konkrete Anforderung speichert oder erste Eingaben in eine Maßnahme macht, wird aus dem jeweiligen Template ein echter Datensatz
* Man muß zuerst eine Anforderung erstellen; optional ordnet man dieser Anforderung noch neue Maßnahmen zu. Aus dieser Zuordnung kann man dann eine echte Maßnahme erstellen
* Eine Anforderung, die keine Maßnahme mehr enthält, gilt als auswirkungslos erledigt und kann geschlossen werden.
* Beim Schliessen bekommt der Benutzer noch die Möglichkeit, ein kurzes Abschlussstatement einzutragen. Da man Anforderungen und Massnahmem auch wieder öffnen und später nochmals schliessen kann, kann es auch mehrere Abschlusstatements geben (also am besten einfach durch einen Eintrag repräsentiert)
* Genauso kann eine Anforderung geschlossen werden, wenn sie nur noch abgeschlossene Maßnahmen enthält
* Eine Anforderung mit offenen Maßnahmen kann nicht geschlossen werden
* Eine abgeschlossene Maßnahme kann auch anderen Anforderungen zugeordnet werden, wenn sich Anforderung und Massnahme im gleichen Teilebaum befinden, denn es kann ja durchaus sein, das eine Maßnahme mehrere Anforderungen löst.
* Geschlossene Anforderungen und Maßnahmen sind nicht verriegelt und können immer wieder neu geöffnet werden.
* Solange Anforderungen oder Maßnahmen geschlossen sind, können sie nicht mehr editiert werden
* Wird eine Maßnahme wieder geöffnet um sie zu verändern, werden auch alle Anforderungen wieder geöffnet, die mit dieser Maßnahme geschlossen wurden. Dadurch soll erreicht werden, dass nochmals geprüft wird, dass auch mit der nachträglich geänderten Maßnahme die Anforderung immer noch erfüllt ist.

### Anforderungen & Maßnahmen - Templates
Mit der Erstellung dieser Templates kommen nur die Admins in Berührung, darum darf dieser Teil ruhig rudimentär ausfallen
So ein Template enthält
* wer muß was tun (Rolle)
* Hilfetext (Markdown)
* Datenblob (JSON)
* Eingabe-Schema (JSON Schema)

## API
Wie oben gesagt, beinhaltet die Anwendung selber nur die rudimentären Elemente, um sie mit Daten zu füllen und die täglichen Aufgaben abarbeiten zu können. Es muß aber umfassend möglich sein, sie von aussen mit Daten zu füllen, die Daten zu verändern (Eigenschaften, Parameter sowie Bauteile hinzufügen, entfernen und die Parent- Zuordnungen), Anforderungen auszulösen (z.B. Kühlwasser nachfüllen bei remote festgestelltem Niedrigstand) und möglichst flexible Abfragen und Auswertungen zu tätigen. Hier dürfte sich GraphGL als Interface anbieten(?)

## Spätere Erweiterungen
Um die Anforderungen in der ersten Version nicht ausufern zu lassen, sollen hier die Dinge genannt sein, die man bei Erfolg noch nachrüsten könnte
* QR-Code Reader Support im Webbrowser, um Teilenummern schnell einlesen zu können
* Eine Massen- Manipulationsseite, wo eine ganze Anzahl von Bauteilen über Filterfunktionen und/oder manuell ausgewählt werden können, um ihnen allen dann eine gemeinsame Anforderung zuweisen zu können (z.B. Austausch einer problematischen Komponente in allen betroffenen Fahrzeugen)

## Entwicklung & Implementierung
* die Entwicklung findet in einem FAUN Gitlab- Repository statt
* die Tools sind freigestellt, aber es ist nur lizenzfreie Open Source Software zu verwenden, bei der durch Bekanntheitsgrad und aktive Community ein gewisser Reifegrad und noch weitere Lebensdauer anzunehmen ist
* es zählt: Weniger ist mehr: Lieber auf ein Gimmick und die dazu gehörigen Libraries verzichten, bevor man eines Tages die Anwendung nicht mehr kompilieren kann, weil sich ein Nodejs- Paket in Luft aufgelöst hat
* die Anwendung muß ohne externes Filesystem in einem Docker Container laufen
* Dieser Docker- Container muß durch ein einfaches docker-compose buid <url> aus dem Repository einsatzbereit sein, konkret muß man ihn durch einen einfachen Portainer- Stack zum Laufen bekommen können.
* Die Anwendung muß ihren aktuellen Datenstand in ein Docker- Volume speichern (Backup) und von dort aus wieder herstellen können (Restore)
