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
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            padding: 15px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #1e40af;
        }
        .summary-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #000;
        }
        .rag-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .rag-item {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            color: white;
            font-weight: bold;
        }
        .rag-item.green {
            background: #10b981;
        }
        .rag-item.amber {
            background: #f59e0b;
        }
        .rag-item.red {
            background: #ef4444;
        }
        .rag-item .label {
            font-size: 12px;
            opacity: 0.9;
        }
        .rag-item .value {
            font-size: 24px;
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
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
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
        .progress-bar {
            width: 100%;
            height: 15px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #f59e0b 80%, #ef4444 100%);
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Portfolio PRISM</h1>
        <p><strong>Généré le:</strong> {{ $generated_at->format('d/m/Y à H:i') }}</p>
    </div>

    <!-- Résumé Exécutif -->
    <div class="section">
        <h2>Résumé Exécutif</h2>
        
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Projets</h3>
                <div class="value">{{ $summary['total_projects'] }}</div>
            </div>
            <div class="summary-card">
                <h3>Complétion Moyenne</h3>
                <div class="value">{{ number_format($summary['avg_completion'], 1) }}%</div>
            </div>
            <div class="summary-card">
                <h3>Risques Totaux</h3>
                <div class="value">{{ $summary['total_risks'] }}</div>
            </div>
        </div>

        <h3>Statut RAG</h3>
        <div class="rag-summary">
            <div class="rag-item green">
                <div class="label">Projets Verts</div>
                <div class="value">{{ $summary['green_projects'] }}</div>
            </div>
            <div class="rag-item amber">
                <div class="label">Projets Orange</div>
                <div class="value">{{ $summary['amber_projects'] }}</div>
            </div>
            <div class="rag-item red">
                <div class="label">Projets Rouges</div>
                <div class="value">{{ $summary['red_projects'] }}</div>
            </div>
        </div>
    </div>

    <!-- Liste des Projets -->
    <div class="section">
        <h2>Liste des Projets ({{ count($projects) }})</h2>
        <table>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Statut</th>
                <th>RAG</th>
                <th>Complétion</th>
                <th>Risques</th>
                <th>Changements</th>
            </tr>
            @foreach($projects as $project)
            <tr>
                <td><strong>{{ $project['code'] }}</strong></td>
                <td>{{ $project['name'] }}</td>
                <td>{{ $project['status'] }}</td>
                <td><span class="badge badge-{{ $project['rag_status'] }}">{{ strtoupper($project['rag_status']) }}</span></td>
                <td>
                    {{ $project['completion'] }}%
                    <div class="progress-bar" style="margin-top: 4px;">
                        <div class="progress-fill" style="width: {{ $project['completion'] }}%;"></div>
                    </div>
                </td>
                <td>{{ $project['risks'] }}</td>
                <td>{{ $project['changes'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>

    <!-- Par Catégorie -->
    @if(!empty($by_category))
    <div class="section">
        <h2>Répartition par Catégorie</h2>
        <table>
            <tr>
                <th>Catégorie</th>
                <th>Nombre</th>
                <th>Verts</th>
                <th>Orange</th>
                <th>Rouges</th>
                <th>Complétion Moy.</th>
            </tr>
            @foreach($by_category as $category => $data)
            <tr>
                <td><strong>{{ $category ?? 'Sans catégorie' }}</strong></td>
                <td>{{ $data['count'] }}</td>
                <td><span class="badge badge-green">{{ $data['green'] }}</span></td>
                <td><span class="badge badge-amber">{{ $data['amber'] }}</span></td>
                <td><span class="badge badge-red">{{ $data['red'] }}</span></td>
                <td>{{ number_format($data['avg_completion'], 1) }}%</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <!-- Par Statut -->
    @if(!empty($by_status))
    <div class="section">
        <h2>Répartition par Statut de Développement</h2>
        <table>
            <tr>
                <th>Statut</th>
                <th>Nombre</th>
                <th>Verts</th>
                <th>Orange</th>
                <th>Rouges</th>
            </tr>
            @foreach($by_status as $status => $data)
            <tr>
                <td><strong>{{ $status }}</strong></td>
                <td>{{ $data['count'] }}</td>
                <td><span class="badge badge-green">{{ $data['green'] }}</span></td>
                <td><span class="badge badge-amber">{{ $data['amber'] }}</span></td>
                <td><span class="badge badge-red">{{ $data['red'] }}</span></td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Rapport PRISM généré automatiquement • Confidentiellement</p>
    </div>
</body>
</html>
