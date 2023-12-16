# queue


`composer require controleonline/queue:dev-master`



Create a new fila on controllers:
config\routes\controllers\queue.yaml

```yaml
controllers:
    resource: ../../vendor/controleonline/queue/src/Controller/
    type: annotation      
```

Add to entities:
nelsys-api\config\packages\doctrine.yaml
```yaml
doctrine:
    orm:
        mappings:
           queue:
                is_bundle: false
                type: annotation
                dir: "%kernel.project_dir%/vendor/controleonline/queue/src/Entity"
                prefix: 'ControleOnline\Entity'
                alias: ControleOnline                             
```          


Add this line on your routes:
config\packages\api_platform.yaml
```yaml          
mapping   :
    paths: ['%kernel.project_dir%/src/Entity','%kernel.project_dir%/src/Resource',"%kernel.project_dir%/vendor/controleonline/queue/src/Entity"]        
```          
