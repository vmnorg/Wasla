# Graph Report - Wasla  (2026-09-25)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 36 nodes · 46 edges · 7 communities (2 shown, 5 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f0d2b1a0`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- index.php
- Database
- process_nlu.php
- switchMapTab
- db.php
- focusMap
- runAggregation

## God Nodes (most connected - your core abstractions)
1. `Database` - 7 edges
2. `switchMapTab()` - 5 edges
3. `closeEsgModal()` - 2 edges
4. `getCoopLayer()` - 2 edges
5. `openEsgModal()` - 2 edges
6. `resetMapView()` - 2 edges
7. `focusMap()` - 2 edges
8. `renderCommunes()` - 2 edges
9. `runAggregation()` - 2 edges
10. `simulateLotAggregation()` - 2 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Import Cycles
- None detected.

## Communities (7 total, 5 thin omitted)

### Community 0 - "index.php"
Cohesion: 0.14
Nodes (10): communesData, coopHubs, mapElement, mapStyleBtn, micBtn, micRecBadge, micStatusText, regionCentroid (+2 more)

### Community 3 - "switchMapTab"
Cohesion: 0.40
Nodes (5): closeEsgModal(), getCoopLayer(), openEsgModal(), resetMapView(), switchMapTab()

## Knowledge Gaps
- **10 isolated node(s):** `communesData`, `coopHubs`, `mapElement`, `mapStyleBtn`, `micBtn` (+5 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 17 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **5 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Database` to `process_nlu.php`, `db.php`?**
  _High betweenness centrality (0.057) - this node is a cross-community bridge._
- **Why does `switchMapTab()` connect `switchMapTab` to `index.php`?**
  _High betweenness centrality (0.005) - this node is a cross-community bridge._
- **What connects `communesData`, `coopHubs`, `mapElement` to the rest of the system?**
  _10 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `index.php` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._