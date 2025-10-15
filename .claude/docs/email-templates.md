# ClientXCMS Email Templates Documentation

## Sources
- https://docs.clientxcms.com/developpers/extensions/implementation_guides/email
- https://docs.clientxcms.com/developpers/extensions/definitions/models

## Email Template Syntax

### Supported Features
- Laravel Blade templating engine
- Multilingual templates (fr_FR, en_GB, es_ES)
- Dynamic content insertion using Blade syntax

### Syntax Elements
- `{{ }}` for variable output
- `@foreach` loops for iterating through collections
- HTML formatting within email body

### Example from Documentation
```json
{
  "fund": {
    "fr_FR": {
      "subject": "Facture payée",
      "button": "Voir la facture",
      "body": "@foreach($invoice->items as $item)\n<strong>Nom</strong> : {{ $item->name }}\n@endforeach"
    }
  }
}
```

### Special Functions
- `formatted_price($amount, $currency)` for currency formatting
- Direct access to object properties and methods
- Nested object traversal supported

## Key Notes
- Templates are stored in `emails.json` at extension root
- Templates can be imported to database using seeders
- Blade rendering happens through `EmailTemplate::bladeRender()` method
