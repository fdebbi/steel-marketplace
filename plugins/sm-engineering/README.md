# SM Engineering

Ingénieurs partenaires et missions d'ingénierie de la plateforme. Nécessite SM Core 0.6.0.

## Principe

1. L'ingénieur postule sur « Devenir ingénieur partenaire » (compte séparé, diplôme et pièces). L'administrateur valide ou refuse (SM → Ingénieurs partenaires).
2. Un client, une société ou un professionnel envoie une demande d'étude ou d'inspection, éventuellement avec un ingénieur préféré choisi dans la liste publique.
3. L'administrateur envoie le devis (SM → Missions d'ingénierie). Le client l'accepte.
4. L'administrateur confie la mission à un ingénieur validé avec ses honoraires. L'ingénieur accepte, dépose ses livrables et les envoie.
5. L'administrateur vérifie, livre au client (ou demande des corrections), puis enregistre le paiement des honoraires.

Le client ne voit jamais l'ingénieur ni ses honoraires ; l'ingénieur ne voit jamais le client ni son prix.

## Réglages

SM → Réglages ingénierie : candidatures ouvertes, liste publique, validité du devis, demandes par jour, fichiers (nombre, taille, extensions), types d'étude (FR/AR, masquer, ajouter).

## Données

Tables `sm_engineers`, `sm_missions`, `sm_mission_files`, `sm_mission_messages`. Fichiers dans le dossier privé de SM Core (`…/missions/`, `…/engineers/`).

## Tests

```
wp eval-file wp-content/plugins/sm-engineering/tests/run.php
python3 tests/e2e-engineering.py http://localhost:8080 /chemin/wordpress sortie/
```
