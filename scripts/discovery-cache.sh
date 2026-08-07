#!/bin/sh
# Progressive Disclosure script for Agent Skills open standard
# 
# Según el Agent Skills spec (agentskills.io):
#   Fase 1 - Discovery: solo nombre + description
#   Fase 2 - Activation: todo el SKILL.md
#   Fase 3 - Execution: scripts + references
#
# Este script comprime skills a su fase 1 (discovery) para ahorrar tokens.

SKILL_DIR=".ai/skills"
DISCOVERY_CACHE=".ai/cache/skills-discovery.md"

mkdir -p .ai/cache 2>/dev/null

echo "# Discovery Index — Agent Skills (Fase 1)" > "$DISCOVERY_CACHE"
echo "" >> "$DISCOVERY_CACHE"
echo "> Progressive disclosure: solo nombre + descripción cargado." >> "$DISCOVERY_CACHE"
echo "> Full body se carga cuando el agente activa la skill." >> "$DISCOVERY_CACHE"
echo "" >> "$DISCOVERY_CACHE"

for skill_md in $SKILL_DIR/*/SKILL.md; do
    if [ -f "$skill_md" ]; then
        name=$(head -30 "$skill_md" | grep -m1 "^name:" | sed 's/^name: *//')
        desc=$(head -30 "$skill_md" | grep -m1 "^description:" | sed 's/^description: *//')
        
        echo "## \`$name\`" >> "$DISCOVERY_CACHE"
        echo "" >> "$DISCOVERY_CACHE"
        echo "$desc" >> "$DISCOVERY_CACHE"
        echo "" >> "$DISCOVERY_CACHE"
    fi
done

echo "✅ Discovery cache generado: $DISCOVERY_CACHE"
wc -c "$DISCOVERY_CACHE"
