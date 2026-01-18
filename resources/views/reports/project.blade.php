<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #2563eb;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section h2 {
            background: #dbeafe;
            padding: 10px 15px;
            margin: 0 0 15px 0;
            border-left: 4px solid #2563eb;
            color: #1e40af;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            padding: 10px;
            background: #f9fafb;
            border-left: 3px solid #e5e7eb;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 16px;
            color: #000;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #2563eb;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .status-green {
            color: #10b981;
            font-weight: bold;
        }
        .status-amber {
            color: #f59e0b;
            font-weight: bold;
        }
        .status-red {
            color: #ef4444;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-green {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-amber {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #999;
            text-align: center;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #f59e0b 80%, #ef4444 100%);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $project['name'] }}</h1>
        <p><strong>Code:</strong> {{ $project['code'] }}</p>
        <p><strong>Catégorie:</strong> {{ $project['category'] ?? 'N/A' }}</p>
        <p><strong>Généré le:</strong> {{ $generated_at->format('d/m/Y à H:i') }}</p>
    </div>

    <!-- Overview -->
    <div class="section">
        <h2>Vue d'ensemble</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Statut de Développement</div>
                <div class="info-value">{{ $project['status'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Statut RAG</div>
                <div class="info-value">
                    <span class="badge badge-{{ $project['rag_status'] }}">{{ strtoupper($project['rag_status']) }}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Complétion</div>
                <div class="info-value">{{ $project['completion'] }}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $project['completion'] }}%"></div>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Phases</div>
                <div class="info-value">{{ $overview['completed_phases'] }} / {{ $overview['total_phases'] }} complétées</div>
            </div>
        </div>

        <table>
            <tr>
                <th>Métrique</th>
                <th>Valeur</th>
            </tr>
            <tr>
                <td>Risques Totaux</td>
                <td>{{ $overview['total_risks'] }} ({{ $overview['high_risks'] }} hauts)</td>
            </tr>
            <tr>
                <td>Demandes de Changement</td>
                <td>{{ $overview['total_changes'] }} ({{ $overview['pending_changes'] }} en attente)</td>
            </tr>
            <tr>
                <td>Date Cible</td>
                <td>{{ $project['target_date'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Date de Soumission</td>
                <td>{{ $project['submission_date'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Risques -->
    @if(!empty($risks))
    <div class="section">
        <h2>Risques ({{ count($risks) }})</h2>
        <table>
            <tr>
                <th>Code</th>
                <th>Description</th>
                <th>Score</th>
                <th>Statut</th>
            </tr>
            @foreach($risks as $risk)
            <tr>
                <td><strong>{{ $risk['code'] }}</strong></td>
                <td>{{ $risk['description'] }}</td>
                <td>{{ $risk['score'] }}</td>
                <td><span class="badge badge-{{ $risk['status'] === 'active' ? 'red' : 'green' }}">{{ $risk['status'] }}</span></td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- Changements -->
    @if(!empty($changes))
    <div class="section">
        <h2>Demandes de Changement ({{ count($changes) }})</h2>
        <table>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Description</th>
                <th>Statut</th>
            </tr>
            @foreach($changes as $change)
            <tr>
                <td><strong>{{ $change['code'] }}</strong></td>
                <td>{{ $change['type'] }}</td>
                <td>{{ $change['description'] }}</td>
                <td><span class="badge badge-{{ $change['status'] === 'approved' ? 'green' : 'amber' }}">{{ $change['status'] }}</span></td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- Phases -->
    @if(!empty($phases))
    <div class="section">
        <h2>Phases ({{ count($phases) }})</h2>
        <table>
            <tr>
                <th>Phase</th>
                <th>Statut</th>
                <th>Date Début</th>
                <th>Date Fin</th>
            </tr>
            @foreach($phases as $phase)
            <tr>
                <td><strong>{{ $phase['name'] }}</strong></td>
                <td><span class="badge badge-{{ $phase['status'] === 'completed' ? 'green' : 'amber' }}">{{ $phase['status'] }}</span></td>
                <td>{{ $phase['start_date'] ?? 'N/A' }}</td>
                <td>{{ $phase['end_date'] ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Rapport PRISM généré automatiquement</p>
    </div>
</body>
</html>
