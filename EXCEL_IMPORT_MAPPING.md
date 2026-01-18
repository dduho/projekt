# MAPPING EXCEL IMPORT - MOOV PROJECT PORTFOLIO

## Vue d'ensemble

Ce document décrit le mapping exact entre les colonnes du fichier Excel source et les champs de la base de données.

---

## 1. PROJECT REGISTER → Table `projects`

| Colonne Excel | Lettre | Champ DB | Type | Transformation |
|---------------|--------|----------|------|----------------|
| ID | A | project_code | string(20) | Direct (MOOV-XXX) |
| Project Name | B | name | string(255) | trim() |
| Submission Date | C | submission_date | date | parseDate() |
| Category | D | category_id | foreign | getOrCreateCategory() |
| Business Area | E | business_area | string(100) | trim() |
| Priority | F | priority | enum | normalizePriority() |
| Description | G | description | text | trim() |
| Planned Release | H | planned_release | string(50) | trim() |
| FRS Status | I | frs_status | enum | normalizeFrsStatus() |
| Development Status | J | dev_status | enum | normalizeDevStatus() |
| Current Progress | K | current_progress | string(100) | trim() |
| Blockers | L | blockers | text | trim() |
| Target Date | M | target_date | date | parseTargetDate() |
| Owner | N | owner_id | foreign | getOrCreateOwner() |
| Last Update | O | - | - | **Non importé** |
| RAG Status | P | rag_status | enum | normalizeRagStatus() |

### Valeurs enum normalisées

#### Priority
- **Input**: High, Medium, Low, Haute, Basse, Critical
- **Output**: High, Medium, Low

#### FRS Status
- **Input**: Draft, Review, Signoff, Deployed, Pending Signoff
- **Output**: Draft, Review, Signoff

#### Development Status
- **Input**: Not Started, In Development, Testing, UAT, Deployed, On Hold, Pending, Waiting
- **Output**: Not Started, In Development, Testing, UAT, Deployed

#### RAG Status
- **Input**: Green, Amber, Red, Vert, Rouge, Orange, Yellow, OK, Critical
- **Output**: Green, Amber, Red

---

## 2. STATUS TRACKING → Tables `projects` + `project_phases`

| Colonne Excel | Lettre | Champ DB | Type | Transformation |
|---------------|--------|----------|------|----------------|
| Project # | A | project_code | string(20) | Lookup project_id |
| Project Name | B | - | - | Référence uniquement |
| PRIORITY | C | - | - | **Ignoré** (déjà dans PROJECT REGISTER) |
| Service Type | D | - | - | **Ignoré** |
| Phase | E (FRS) | project_phases.status | enum | parsePhaseStatus() |
| | F (Dev) | project_phases.status | enum | parsePhaseStatus() |
| | G (Testing) | project_phases.status | enum | parsePhaseStatus() |
| | H (UAT) | project_phases.status | enum | parsePhaseStatus() |
| | I (Deploy) | project_phases.status | enum | parsePhaseStatus() |
| Completion % | J | completion_percent | int(0-100) | intval() |
| Health | K | rag_status | enum | normalizeRagStatus() |

### Phase Status Mapping

| Symbole Excel | Status DB |
|---------------|-----------|
| ✓, ✔, Yes, Done, Complete | Completed |
| ⏳, In Progress, WIP | In Progress |
| ⏸, On Hold, Paused | On Hold |
| ❌, Blocked, Failed | Blocked |
| -, X, Empty | Pending |

### Phases créées (5 par projet)

1. **FRS** (Colonne E)
2. **Development** (Colonne F)
3. **Testing** (Colonne G)
4. **UAT** (Colonne H)
5. **Deployment** (Colonne I)

---

## 3. RISK & ISSUES LOG → Table `risks`

| Colonne Excel | Lettre | Champ DB | Type | Transformation |
|---------------|--------|----------|------|----------------|
| Risk/Issue Code | A | risk_code | string(20) | Direct (RISK-XXX) |
| Type | B | type | enum | Risk ou Issue |
| Project # | C | project_id | foreign | Lookup via project_code |
| Description | D | description | text | trim() |
| Impact | E | impact | enum | normalizePriority() |
| Probability | F | probability | enum | normalizePriority() |
| Risk Score | G | risk_score | enum | normalizeRiskScore() |
| Mitigation Plan | H | mitigation_plan | text | trim() |
| Owner | I | owner | string(100) | trim() |
| Status | J | status | enum | normalizeRiskStatus() |

### Risk Status Values
- **Input**: Open, In Progress, Mitigated, Closed, Fermé, Atténué
- **Output**: Open, In Progress, Mitigated, Closed

### Risk Score Values
- **Input**: Critical, High, Medium, Low
- **Output**: Critical, High, Medium, Low

---

## 4. CHANGE LOG → Table `change_requests`

| Colonne Excel | Lettre | Champ DB | Type | Transformation |
|---------------|--------|----------|------|----------------|
| Change Code | A | change_code | string(20) | Direct (CHG-XXX) |
| Date | B | requested_at | timestamp | parseDate() |
| Project # | C | project_id | foreign | Lookup via project_code |
| Type | D | change_type | enum | normalizeChangeType() |
| Description | E | description | text | trim() |
| Requested By | F | requested_by_id | foreign | getOrCreateUser() |
| Approved By | G | approved_by_id | foreign | getOrCreateUser() |
| Status | H | status | enum | normalizeChangeStatus() |

### Change Type Values
- **Input**: Scope, Schedule, Budget, Resource, Planning, Date, Cost, Team
- **Output**: Scope Change, Schedule Change, Budget Change, Resource Change

### Change Status Values
- **Input**: Pending, Under Review, Approved, Rejected, Approuvé, Rejeté
- **Output**: Pending, Under Review, Approved, Rejected

---

## 5. Date Parsing

### Formats supportés

1. **Excel Serial Number**: 45678 → converti via ExcelDate::excelToDateTimeObject()
2. **Month YYYY**: "January 2025", "July 2025" → Premier jour du mois
3. **DD/MM/YYYY**: "15/01/2026" → Format ISO
4. **ISO**: "2026-01-15" → Direct
5. **Valeurs spéciales**: "TBD", "Need PO", "Completed", "NaN" → NULL

---

## 6. Statistiques d'import

Le service retourne des statistiques détaillées :

```php
[
    'success' => true,
    'stats' => [
        'projects' => ['created' => X, 'updated' => Y, 'errors' => Z],
        'phases' => ['created' => X, 'updated' => Y, 'errors' => Z],
        'risks' => ['created' => X, 'updated' => Y, 'errors' => Z],
        'changes' => ['created' => X, 'updated' => Y, 'errors' => Z],
    ],
    'errors' => [/* Liste des erreurs */],
    'duration' => 12.34, // secondes
]
```

---

## 7. Commande CLI

### Import complet
```bash
docker-compose exec app php artisan projects:import storage/moov_portfolio.xlsx
```

### Preview sans import
```bash
docker-compose exec app php artisan projects:import storage/moov_portfolio.xlsx --preview
```

### Import sans confirmation
```bash
docker-compose exec app php artisan projects:import storage/moov_portfolio.xlsx --force
```

---

## 8. Validation avant import

### Feuilles Excel requises
1. ✅ PROJECT REGISTER
2. ✅ STATUS TRACKING
3. ✅ RISK & ISSUES LOG
4. ✅ CHANGE LOG

### Contrôles effectués
- ✅ Vérification existence des feuilles
- ✅ Validation format des codes (MOOV-XXX, RISK-XXX, CHG-XXX)
- ✅ Vérification foreign keys (project_code existe)
- ✅ Normalisation des enums
- ✅ Parsing robuste des dates

---

## 9. Gestion des erreurs

### Types d'erreurs capturées
1. **Projet non trouvé**: Risque/Change référence un project_code inexistant
2. **Date invalide**: Format non reconnu
3. **Enum invalide**: Valeur non mappée (utilise valeur par défaut)
4. **Contrainte DB**: Violation de contrainte unique ou foreign key

### Comportement
- ✅ **Transaction DB**: Rollback complet en cas d'erreur critique
- ✅ **Erreurs partielles**: Comptabilisées dans stats['errors']
- ✅ **Log détaillé**: Toutes erreurs loggées avec ligne Excel

---

## 10. Conformité CDC

| Critère | Status | Note |
|---------|--------|------|
| 16 champs projects | ✅ | Tous mappés |
| 5 phases par projet | ✅ | FRS, Dev, Testing, UAT, Deploy |
| Risques avec score | ✅ | Calcul automatique Impact x Probability |
| Changes avec workflow | ✅ | 4 statuts supportés |
| Import upsert | ✅ | Mise à jour si project_code existe |
| Preview mode | ✅ | --preview flag disponible |
| Statistiques | ✅ | Détaillées par type |
| Validation | ✅ | Feuilles et format vérifiés |

---

## 11. Notes importantes

### ⚠️ Données non importées
- **Last Update** (col O) de PROJECT REGISTER → géré par timestamps Laravel
- **Service Type** (col D) de STATUS TRACKING → n'existe pas dans le modèle
- **Remarks** colonnes supplémentaires → non spécifiées dans CDC

### ✅ Auto-générations
- **Categories**: Créées automatiquement avec couleurs par défaut
- **Owners**: Utilisateurs "équipe" créés si nom inconnu (email: slug@moov.tg)
- **Project phases**: 5 phases créées automatiquement à la création d'un projet

### 🔄 Upsert logic
- **project_code** unique → UPDATE si existe, INSERT sinon
- **Phases** → UPDATE sur (project_id, phase)
- **Risks** → UPDATE sur risk_code
- **Changes** → UPDATE sur change_code

---

**Document généré**: 2026-01-17
**Version**: 1.0
**Conforme à**: CAHIER DES CHARGES v1.0
