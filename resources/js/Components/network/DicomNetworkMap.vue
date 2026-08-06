<script setup lang="ts">
import {
    MarkerType,
    VueFlow,
    useVueFlow,
    type Edge,
    type EdgeMouseEvent,
    type Node,
    type NodeMouseEvent,
} from '@vue-flow/core';
import { Maximize2 } from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import DicomConnectionDetails from './DicomConnectionDetails.vue';
import DicomNetworkNode, { type DicomMapNodeData } from './DicomNetworkNode.vue';
import DicomNodeDetails from './DicomNodeDetails.vue';
import SystemGroupNode, { type SystemGroupData } from './SystemGroupNode.vue';

export type NetworkNode = {
    id: number;
    public_id: string;
    name: string;
    ae_title: string;
    host: string;
    port: number;
    role: string;
    status: string;
    tls_enabled: boolean;
    last_verified_at: string | null;
    last_verification_status: string | null;
    last_verification_duration_ms: number | null;
    system: {
        public_id: string;
        name: string;
        system_type: string;
        status: string;
        organization: string | null;
        site: string | null;
        department: string | null;
    };
};

export type NetworkConnection = {
    public_id: string;
    name: string;
    service: string;
    status: string;
    evidence_status: string;
    source_node_id: number;
    target_node_id: number;
    destination_node_id: number | null;
    calling_ae_title: string;
    called_ae_title: string;
    port: number | null;
    tls_enabled: boolean;
    test_enabled: boolean;
};

type TopologyNodeData = DicomMapNodeData | SystemGroupData;

const props = withDefaults(
    defineProps<{
        nodes: NetworkNode[];
        connections: NetworkConnection[];
        focusSystemPublicId?: string | null;
        focusNodePublicId?: string | null;
        focusConnectionPublicId?: string | null;
        compact?: boolean;
        detailsEnabled?: boolean;
        layoutMode?: 'wide' | 'balanced';
    }>(),
    {
        focusSystemPublicId: null,
        focusNodePublicId: null,
        focusConnectionPublicId: null,
        compact: false,
        detailsEnabled: true,
        layoutMode: 'wide',
    },
);

const { fitView } = useVueFlow();

const selectedNode = ref<NetworkNode | null>(null);
const nodeDetailsOpen = ref(false);

const selectedConnection = ref<NetworkConnection | null>(null);
const connectionDetailsOpen = ref(false);

const serviceLabels: Record<string, string> = {
    echo: 'C-ECHO',
    store: 'C-STORE',
    worklist: 'Worklist',
    query: 'Query',
    move: 'C-MOVE',
    get: 'C-GET',
};

const serviceStroke: Record<string, string> = {
    echo: '#64748b',
    store: '#2563eb',
    worklist: '#7c3aed',
    query: '#0891b2',
    move: '#d97706',
    get: '#059669',
};

const selectedSourceNode = computed<NetworkNode | null>(() => {
    if (selectedConnection.value === null) {
        return null;
    }

    return props.nodes.find((node) => node.id === selectedConnection.value?.source_node_id) ?? null;
});

const selectedTargetNode = computed<NetworkNode | null>(() => {
    if (selectedConnection.value === null) {
        return null;
    }

    return props.nodes.find((node) => node.id === selectedConnection.value?.target_node_id) ?? null;
});

const localNodeIds = computed(() => {
    if (props.focusSystemPublicId === null) {
        return new Set<number>();
    }

    return new Set(
        props.nodes.filter((node) => node.system.public_id === props.focusSystemPublicId).map((node) => node.id),
    );
});

const visibleConnections = computed(() => {
    if (props.focusSystemPublicId === null) {
        return props.connections;
    }

    return props.connections.filter(
        (connection) =>
            localNodeIds.value.has(connection.source_node_id) || localNodeIds.value.has(connection.target_node_id),
    );
});

const visibleNodeIds = computed(() => {
    if (props.focusSystemPublicId === null) {
        return new Set(props.nodes.map((node) => node.id));
    }

    const ids = new Set(localNodeIds.value);

    for (const connection of visibleConnections.value) {
        ids.add(connection.source_node_id);
        ids.add(connection.target_node_id);
    }

    return ids;
});

const visibleNodes = computed(() => props.nodes.filter((node) => visibleNodeIds.value.has(node.id)));

const focusedNodeIds = computed(() => {
    if (selectedNode.value === null) {
        return new Set<number>();
    }

    const ids = new Set<number>([selectedNode.value.id]);

    for (const connection of visibleConnections.value) {
        if (connection.source_node_id === selectedNode.value.id) {
            ids.add(connection.target_node_id);
        }

        if (connection.target_node_id === selectedNode.value.id) {
            ids.add(connection.source_node_id);
        }
    }

    return ids;
});

const hasNodeFocus = computed(() => selectedNode.value !== null && nodeDetailsOpen.value);

const flowNodes = computed<Node<TopologyNodeData>[]>(() => {
    const grouped = new Map<string, NetworkNode[]>();

    for (const node of visibleNodes.value) {
        const systemNodes = grouped.get(node.system.public_id) ?? [];

        systemNodes.push(node);
        grouped.set(node.system.public_id, systemNodes);
    }

    const result: Node<TopologyNodeData>[] = [];
    const systems = Array.from(grouped.entries());
    const columns = props.compact
        ? Math.min(Math.max(systems.length, 1), 2)
        : props.layoutMode === 'wide' && systems.length <= 6
          ? Math.max(systems.length, 1)
          : Math.max(1, Math.ceil(Math.sqrt(systems.length)));

    const groupWidth = props.compact ? 330 : 310;
    const nodeHeight = props.compact ? 205 : 188;
    const groupHeaderHeight = props.compact ? 96 : 84;
    const groupPadding = props.compact ? 26 : 22;
    const groupGapX = props.compact ? 70 : props.layoutMode === 'wide' ? 62 : 92;
    const groupGapY = props.compact ? 60 : 72;

    systems.forEach(([systemPublicId, systemNodes], systemIndex) => {
        const column = systemIndex % columns;
        const row = Math.floor(systemIndex / columns);
        const groupHeight = groupHeaderHeight + groupPadding + systemNodes.length * nodeHeight + groupPadding;

        const x = column * (groupWidth + groupGapX);
        const previousRows = systems.slice(0, row * columns).filter((_, index) => index % columns === column);

        const y = previousRows.reduce(
            (offset, [, rowNodes]) =>
                offset + groupHeaderHeight + groupPadding * 2 + rowNodes.length * nodeHeight + groupGapY,
            0,
        );

        const firstNode = systemNodes[0];

        result.push({
            id: `system-${systemPublicId}`,
            type: 'systemGroup',
            position: { x, y },
            data: {
                publicId: systemPublicId,
                name: firstNode.system.name,
                systemType: firstNode.system.system_type,
                status: firstNode.system.status,
                organization: firstNode.system.organization,
                site: firstNode.system.site,
                department: firstNode.system.department,
                nodeCount: systemNodes.length,
                failedCount: systemNodes.filter(
                    (node) => node.last_verification_status !== null && node.last_verification_status !== 'success',
                ).length,
                unverifiedCount: systemNodes.filter((node) => node.last_verified_at === null).length,
            },
            style: {
                width: `${groupWidth}px`,
                height: `${groupHeight}px`,
                opacity:
                    hasNodeFocus.value && !systemNodes.some((node) => focusedNodeIds.value.has(node.id)) ? 0.32 : 1,
                transition: 'opacity 180ms ease',
            },
            draggable: !props.compact,
            selectable: false,
            connectable: false,
            zIndex: -1,
        });

        systemNodes.forEach((node, nodeIndex) => {
            result.push({
                id: String(node.id),
                type: 'dicomNode',
                parentNode: `system-${systemPublicId}`,
                extent: 'parent',
                position: {
                    x: groupPadding + 10,
                    y: groupHeaderHeight + groupPadding + nodeIndex * nodeHeight,
                },
                data: {
                    publicId: node.public_id,
                    name: node.name,
                    aeTitle: node.ae_title,
                    host: node.host,
                    port: node.port,
                    role: node.role,
                    tlsEnabled: node.tls_enabled,
                    verificationStatus: node.last_verification_status,
                    verificationDurationMs: node.last_verification_duration_ms,
                    systemPublicId,
                    systemName: node.system.name,
                    systemType: node.system.system_type,
                },
                draggable: !props.compact,
                selectable: props.detailsEnabled,
                zIndex: focusedNodeIds.value.has(node.id) ? 12 : 1,
                style: {
                    opacity: hasNodeFocus.value && !focusedNodeIds.value.has(node.id) ? 0.24 : 1,
                    transition: 'opacity 180ms ease',
                },
            });
        });
    });

    return result;
});

const flowEdges = computed<Edge[]>(() =>
    visibleConnections.value.map((connection) => {
        const selected = selectedConnection.value?.public_id === connection.public_id;
        const connectedToFocusedNode =
            selectedNode.value !== null &&
            (connection.source_node_id === selectedNode.value.id ||
                connection.target_node_id === selectedNode.value.id);
        const dimmedByNodeFocus = hasNodeFocus.value && !connectedToFocusedNode;

        const color =
            connection.evidence_status === 'failed_last_test'
                ? '#dc2626'
                : (serviceStroke[connection.service] ?? '#64748b');
        // Linienstil nach Nachweis-Status: durchgezogen (bestätigt/aktiv), gestrichelt (vermutet),
        // gepunktet (technisch getestet, aber nicht bestätigt). Ein erfolgreicher C-ECHO allein
        // erzeugt nie eine bestätigte Verbindung - das entscheidet ausschließlich der Benutzer.
        const strokeDasharray =
            connection.evidence_status === 'suspected'
                ? '8 6'
                : connection.evidence_status === 'technically_tested'
                  ? '2 4'
                  : undefined;
        const label =
            (serviceLabels[connection.service] ?? connection.service.toUpperCase()) +
            (connection.evidence_status === 'failed_last_test' ? ' ⚠' : '');

        return {
            id: connection.public_id,
            source: String(connection.source_node_id),
            target: String(connection.target_node_id),
            label,
            type: 'smoothstep',
            animated:
                connection.status === 'active' &&
                connection.evidence_status !== 'failed_last_test' &&
                !selected &&
                !props.compact &&
                (!hasNodeFocus.value || connectedToFocusedNode),
            markerEnd: {
                type: MarkerType.ArrowClosed,
                color,
            },
            style: {
                stroke: color,
                strokeWidth: selected || connectedToFocusedNode ? 4 : 2.5,
                strokeDasharray,
                opacity: dimmedByNodeFocus ? 0.14 : 1,
                filter: selected || connectedToFocusedNode ? 'drop-shadow(0 0 4px rgba(37, 99, 235, 0.35))' : undefined,
                transition: 'opacity 180ms ease',
            },
            labelStyle: {
                fill: connection.evidence_status === 'failed_last_test' ? '#b91c1c' : selected ? '#1d4ed8' : '#334155',
                fontSize: props.compact ? 10 : selected ? 13 : 12,
                fontWeight: 700,
            },
            labelBgStyle: {
                fill: '#ffffff',
                fillOpacity: 0.97,
                stroke: selected ? '#93c5fd' : '#e2e8f0',
                strokeWidth: 1,
            },
            labelBgPadding: [8, 5],
            labelBgBorderRadius: 8,
            zIndex: selected ? 20 : 5,
        };
    }),
);

const fitMap = async (): Promise<void> => {
    await nextTick();

    fitView({
        padding: props.compact ? 0.16 : 0.045,
        duration: 350,
        maxZoom: props.compact ? 0.82 : 1.28,
    });
};

const openNodeDetails = (event: NodeMouseEvent): void => {
    if (!props.detailsEnabled || event.node.type !== 'dicomNode') {
        return;
    }

    closeConnectionDetails();

    const id = Number(event.node.id);

    selectedNode.value = props.nodes.find((node) => node.id === id) ?? null;

    nodeDetailsOpen.value = selectedNode.value !== null;
};

const closeNodeDetails = (): void => {
    nodeDetailsOpen.value = false;
    selectedNode.value = null;
};

const openConnectionDetails = (event: EdgeMouseEvent): void => {
    if (!props.detailsEnabled) {
        return;
    }

    closeNodeDetails();

    selectedConnection.value = props.connections.find((connection) => connection.public_id === event.edge.id) ?? null;

    connectionDetailsOpen.value = selectedConnection.value !== null;
};

const closeConnectionDetails = (): void => {
    connectionDetailsOpen.value = false;
    selectedConnection.value = null;
};

onMounted(() => {
    fitMap();
    if (!props.detailsEnabled) return;
    if (props.focusNodePublicId) {
        selectedNode.value = props.nodes.find((node) => node.public_id === props.focusNodePublicId) ?? null;
        nodeDetailsOpen.value = selectedNode.value !== null;
    } else if (props.focusConnectionPublicId) {
        selectedConnection.value =
            props.connections.find((item) => item.public_id === props.focusConnectionPublicId) ?? null;
        connectionDetailsOpen.value = selectedConnection.value !== null;
    }
});

watch(() => [props.nodes, props.connections, props.focusSystemPublicId, props.layoutMode], fitMap, { deep: true });
</script>

<template>
    <div class="relative overflow-hidden" :class="compact ? 'h-[390px]' : 'h-[720px]'">
        <VueFlow
            :nodes="flowNodes"
            :edges="flowEdges"
            :min-zoom="0.15"
            :max-zoom="1.8"
            :default-viewport="{ x: 0, y: 0, zoom: 0.75 }"
            class="bg-slate-100/70"
            fit-view-on-init
            elevate-edges-on-select
            :nodes-draggable="!compact"
            :elements-selectable="detailsEnabled"
            @node-click="openNodeDetails"
            @edge-click="openConnectionDetails"
        >
            <template #node-systemGroup="nodeProps">
                <SystemGroupNode v-bind="nodeProps" />
            </template>

            <template #node-dicomNode="nodeProps">
                <DicomNetworkNode v-bind="nodeProps" />
            </template>

            <svg>
                <defs>
                    <pattern id="network-grid-v2" width="24" height="24" patternUnits="userSpaceOnUse">
                        <circle cx="1" cy="1" r="1" fill="#cbd5e1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#network-grid-v2)" />
            </svg>
        </VueFlow>

        <button
            type="button"
            class="absolute top-4 right-4 z-10 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
            @click="fitMap"
        >
            <Maximize2 :size="15" />
            Ansicht einpassen
        </button>
    </div>

    <template v-if="detailsEnabled">
        <DicomNodeDetails :open="nodeDetailsOpen" :node="selectedNode" @close="closeNodeDetails" />

        <DicomConnectionDetails
            :open="connectionDetailsOpen"
            :connection="selectedConnection"
            :source-node="selectedSourceNode"
            :target-node="selectedTargetNode"
            @close="closeConnectionDetails"
        />
    </template>
</template>
