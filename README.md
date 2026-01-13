# One Page Checkout per PrestaShop 1.7

Modulo per checkout completo in una sola pagina, con gestione integrata di autenticazione, indirizzi, spedizione e pagamenti.

## Caratteristiche

- **Checkout in una pagina**: Tutti gli step del checkout visibili in una sola schermata
- **Gestione clienti**: Supporta sia clienti registrati che checkout ospite
- **Login inline**: Gli utenti possono fare login senza lasciare la pagina
- **Indirizzi multipli**: I clienti registrati possono scegliere tra indirizzi esistenti
- **Aggiornamento AJAX**: Tutti i cambiamenti (corriere, codici sconto) aggiornano i totali senza ricaricare
- **Integrazione pagamenti**: Compatibile con tutti i moduli di pagamento PrestaShop 1.7
- **Design responsive**: Funziona perfettamente su desktop e mobile
- **Codici sconto**: Applicazione in tempo reale dei codici promozionali

## Installazione

1. Copia la cartella `onepagecheckout` in `/modules/` del tuo PrestaShop
2. Vai nel Back Office > Moduli > Module Manager
3. Cerca "One Page Checkout" e clicca "Installa"
4. Configura le opzioni nel modulo

## Configurazione

Nel back office del modulo puoi configurare:

- **Checkout ospite**: Abilita/disabilita la possibilità di checkout senza registrazione
- **Newsletter**: Mostra/nascondi la checkbox per l'iscrizione alla newsletter
- **Telefono obbligatorio**: Rendi il campo telefono obbligatorio o facoltativo

## Come funziona

### Autenticazione

Il modulo gestisce l'autenticazione in modo sicuro:

1. **Clienti esistenti**: Possono fare login direttamente nella pagina di checkout
2. **Nuovi clienti**: Vengono creati automaticamente quando inseriscono i loro dati
3. **Checkout ospite**: Se abilitato, i clienti possono completare l'ordine senza creare un account

### Pagamenti

Il modulo è compatibile con tutti i moduli di pagamento che supportano PrestaShop 1.7:

- I moduli di pagamento vengono caricati dinamicamente
- Il form di pagamento viene mostrato quando il cliente seleziona un metodo
- Il reindirizzamento al gateway di pagamento avviene solo dopo la validazione completa

### Flusso del checkout

1. Il cliente inserisce i dati personali (nome, cognome, email)
2. I dati vengono salvati automaticamente via AJAX
3. Inserisce l'indirizzo di spedizione
4. Seleziona il corriere (i corrieri disponibili si aggiornano in base all'indirizzo)
5. Seleziona il metodo di pagamento
6. Accetta i termini e condizioni
7. Conferma l'ordine

## URL del checkout

Il checkout è accessibile tramite:

```
https://tuosito.com/module/onepagecheckout/checkout
```

Per reindirizzare il checkout standard a questa pagina, puoi:

1. Usare un override del controller `OrderController`
2. Usare un hook `actionFrontControllerSetMedia`
3. Modificare i link nel tema

## Esempio di override per reindirizzamento

Crea il file `/override/controllers/front/OrderController.php`:

```php
<?php
class OrderController extends OrderControllerCore
{
    public function init()
    {
        // Redirect to one page checkout
        $link = new Link();
        Tools::redirect($link->getModuleLink('onepagecheckout', 'checkout'));
    }
}
```

Dopo aver creato l'override, svuota la cache di PrestaShop.

## Personalizzazione del design

I file CSS e JS sono in:

- `/modules/onepagecheckout/views/css/onepagecheckout.css`
- `/modules/onepagecheckout/views/js/onepagecheckout.js`

Puoi sovrascrivere gli stili nel tuo tema creando:

- `/themes/tuo-tema/modules/onepagecheckout/views/css/custom.css`

## Sicurezza

Il modulo include:

- Validazione token CSRF per tutte le richieste AJAX
- Validazione server-side di tutti gli input
- Protezione contro SQL injection
- Controllo accesso per gli indirizzi cliente

## Compatibilità

- PrestaShop 1.7.0.0 - 1.7.x
- PHP 7.1+
- Tutti i temi basati su Classic

## Supporto

Per problemi o richieste di funzionalità, apri una issue nel repository.

## Licenza

MIT License
