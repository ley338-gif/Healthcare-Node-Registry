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

const props = defineProps<{
    nodes: NetworkNode[];
    connections: NetworkConnection[];
}>();

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

const flowNodes = computed<Node<TopologyNodeData>[]>(() => {
    const grouped = new Map<string, NetworkNode[]>();

    for (const node of props.nodes) {
        const systemNodes = grouped.get(node.system.public_id) ?? [];

        systemNodes.push(node);
        grouped.set(node.system.public_id, systemNodes);
    }

    const result: Node<TopologyNodeData>[] = [];
    const systems = Array.from(grouped.entries());
    const columns = Math.max(1, Math.ceil(Math.sqrt(systems.length)));

    const groupWidth = 330;
    const nodeHeight = 205;
    const groupHeaderHeight = 96;
    const groupPadding = 26;
    const groupGapX = 110;
    const groupGapY = 90;

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
            },
            draggable: true,
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
                draggable: true,
                selectable: true,
                zIndex: 1,
            });
        });
    });

    return result;
});

const flowEdges = computed<Edge[]>(() =>
    props.connections.map((connection) => {
        const selected = selectedConnection.value?.public_id === connection.public_id;

        const color = serviceStroke[connection.service] ?? '#64748b';

        return {
            id: connection.public_id,
            source: String(connection.source_node_id),
            target: String(connection.target_node_id),
            label: serviceLabels[connection.service] ?? connection.service.toUpperCase(),
            type: 'smoothstep',
            animated: connection.status === 'active' && !selected,
            markerEnd: {
                type: MarkerType.ArrowClosed,
                color,
            },
            style: {
                stroke: color,
                strokeWidth: selected ? 4 : 2.5,
                filter: selected ? 'drop-shadow(0 0 4px rgba(37, 99, 235, 0.45))' : undefined,
            },
            labelStyle: {
                fill: selected ? '#1d4ed8' : '#334155',
                fontSize: selected ? 13 : 12,
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
        padding: 0.12,
        duration: 350,
        maxZoom: 1.05,
    });
};

const openNodeDetails = (event: NodeMouseEvent): void => {
    if (event.node.type !== 'dicomNode') {
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
    closeNodeDetails();

    selectedConnection.value = props.connections.find((connection) => connection.public_id === event.edge.id) ?? null;

    connectionDetailsOpen.value = selectedConnection.value !== null;
};

const closeConnectionDetails = (): void => {
    connectionDetailsOpen.value = false;
    selectedConnection.value = null;
};

onMounted(fitMap);

watch(() => [props.nodes, props.connections], fitMap, { deep: true });
</script>

<template>
    <div class="relative h-[720px] overflow-hidden rounded-2xl">
        <VueFlow
            :nodes="flowNodes"
            :edges="flowEdges"
            :min-zoom="0.15"
            :max-zoom="1.8"
            :default-viewport="{ x: 0, y: 0, zoom: 0.75 }"
            class="bg-slate-50"
            fit-view-on-init
            elevate-edges-on-select
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
            class="absolute top-4 right-4 z-10 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            @click="fitMap"
        >
            <Maximize2 :size="15" />
            Einpassen
        </button>
    </div>

    <DicomNodeDetails :open="nodeDetailsOpen" :node="selectedNode" @close="closeNodeDetails" />

    <DicomConnectionDetails
        :open="connectionDetailsOpen"
        :connection="selectedConnection"
        :source-node="selectedSourceNode"
        :target-node="selectedTargetNode"
        @close="closeConnectionDetails"
    />
</template>
