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



## Das techniche Konzept
Um diese Vielzahl von scheinbar völlig unterschiedlichlichen Anforderungen bedienen zu können, werden einige konzeptinelle Annahmen getroffen:
* Ein Fahrzeug besteht aus vielen Bauteilen (und ist im System auch nur ein Bauteil)
* Jedes Bauteil kann in ein übergeordnetes Bauteil eingebaut sein. Das oberste Bauteil einer solchen Kette ist dann das Fahrzeug
* Jedes Bauteil hat natürlich eine Teilenummer, aber auch wo möglich eine Seriennumer. Es wird dadurch individuell mit "persönlicher" Lebensgeschichte
* Jedes Bauteil hat eine Menge (programmiertechnisch ein (geschachteltes) Dictonary) an Eigenschaften wie z.B. Fahrzeugausstattung, Fahrgestellnummer, Kundenandresse usw. und eine Menge an Parametern (Aufenthaltsort, Kilometerstand, Füllmengen usw.). Der Unterschied zwischen Eigenschaften und Parametern liegt darin, dass die Eigenschaften als solches statisch sind und nur durch Benutzereingaben verändert werden, während die Parameter sich dynamisch durch äussere Einflüsse verändern und den aktuellen Ist- Zustand wiedergeben.

### Anforderung und Maßnahmen
Um ein möglichst breites Spektrum an Fällen abzudecken, sind alle Abläufe generalisert in **Anforderung** und eine oder mehrere **Maßnahmen**, welche die Anforderung letztlich erfüllen. Ob die durchgeführten Maßnamhen letztlich die Anforderung erfüllen, entscheidet nicht das System, sondern der Benutzer selber.

