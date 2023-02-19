# Implementačná dokumentácia k 1. úlohe do IPP 2022/2023
Meno a priezvisko: Gabriel Biel
Login: xbielg00
## Úvod:
Tento projekt je skript na filtrovanie, ktorý číta zdrojový kód v IPPcode23 zo štandardného vstupu, kontroluje lexikálnu a syntaktickú správnosť kódu a vypisuje XML reprezentáciu na štandardný výstup. Skript je implementovaný pomocou dvoch tried: Argument parser a Hlavný parser.
## Argument parser:
Argument parser sa zaoberá argumentami, s ktorými používateľ spustí skript. Pre tento skript je povolený iba argument "--help". Pri zadaní "--help" sa vypíše iba použitie. Ak je akýkoľvek argument neuznaný, program skončí a vráti chybový kód 10.
## Main parser:
Main parser sa zaoberá vstupom. Ak je lexikálna alebo syntaktická chyba, vráti chybový kód 23. Ak je vo vstupnom kóde neuznaná operácia, vygeneruje chybový kód 22. Ak je chyba v hlavičke vstupného kódu, vráti chybový kód 10. Ak sa spracovanie podarí, vráti hodnotu 0.
## Generovanie XML kódu:
Main parser je zodpovedný za generovanie XML kódu. Robí tak pomocou metódy instruction(). XML reprezentácia vstupného kódu sa vypisuje na štandardný výstup.