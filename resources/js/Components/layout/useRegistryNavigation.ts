import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    Boxes,
    Building2,
    Cable,
    FileText,
    FlaskConical,
    Map,
    Radar,
    TableProperties,
    Settings,
    ShieldCheck,
} from '@lucide/vue';
import type { PageProps } from '../../types';
export function useRegistryNavigation() {
    const page = usePage<PageProps>();
    const isActive = (paths: string[]) => {
        const path = page.url.split('?')[0];
        return paths.some((p) => (p === '/' ? path === '/' : path === p || path.startsWith(p + '/')));
    };
    const groups = computed(() => {
        const permissions = page.props.auth.permissions;
        const canViewRegistry = permissions.some((p) => ['registry.view', 'registry.manage'].includes(p));
        const canViewConnections = canViewRegistry;
        const canViewDiscovery = permissions.some((p) =>
            ['discovery.view', 'discovery.run', 'discovery.manage'].includes(p),
        );
        const canViewAudit = permissions.includes('audit.view');
        const canViewSettings = permissions.some((p) =>
            ['users.manage', 'roles.manage', 'settings.manage'].includes(p),
        );

        const definitions = [
            {
                label: 'Registry',
                items: [
                    {
                        label: 'Systeme',
                        href: '/systems',
                        paths: ['/systems'],
                        icon: Boxes,
                        enabled: canViewRegistry,
                    },
                    {
                        label: 'Organisation',
                        href: '/structure',
                        paths: ['/structure', '/organizations', '/sites', '/departments'],
                        icon: Building2,
                        enabled: canViewRegistry,
                    },
                    {
                        label: 'Dokumente',
                        href: '/documents',
                        paths: ['/documents'],
                        icon: FileText,
                        enabled: permissions.includes('documents.view'),
                    },
                ],
            },
            {
                label: 'Werkzeuge',
                items: [
                    {
                        label: 'Geräte finden',
                        href: '/discovery',
                        paths: ['/discovery'],
                        icon: Radar,
                        enabled: canViewDiscovery,
                    },
                    {
                        label: 'Tests',
                        href: '/tests',
                        paths: ['/tests'],
                        icon: FlaskConical,
                        enabled: canViewRegistry,
                    },
                ],
            },
            {
                label: 'Netzwerk',
                items: [
                    {
                        label: 'Topologie',
                        href: '/network',
                        paths: ['/network'],
                        icon: Map,
                        enabled: canViewRegistry,
                    },
                    {
                        label: 'Verbindungen',
                        href: '/connections',
                        paths: ['/connections'],
                        icon: Cable,
                        enabled: canViewConnections,
                    },
                    {
                        label: 'Firewall-Matrix',
                        href: '/reports/firewall-matrix',
                        paths: ['/reports/firewall-matrix'],
                        icon: TableProperties,
                        enabled: canViewConnections,
                    },
                ],
            },
            {
                label: 'Verwaltung',
                items: [
                    {
                        label: 'Audit',
                        href: '/audit',
                        paths: ['/audit'],
                        icon: ShieldCheck,
                        enabled: canViewAudit,
                    },
                    {
                        label: 'Einstellungen',
                        href: '/settings',
                        paths: ['/settings'],
                        icon: Settings,
                        enabled: canViewSettings,
                    },
                ],
            },
        ];

        return definitions
            .map((group) => ({ ...group, items: group.items.filter((item) => item.enabled) }))
            .filter((group) => group.items.length > 0);
    });
    const primarySections = computed(() => {
        const registry = groups.value.find((group) => group.label === 'Registry');
        const dicom = ['Netzwerk', 'Werkzeuge'].flatMap((label) => {
            const group = groups.value.find((candidate) => candidate.label === label);
            return group
                ? [
                      {
                          label: group.label,
                          href: group.items[0].href,
                          paths: group.items.flatMap((item) => item.paths),
                          icon: label === 'Netzwerk' ? Map : Radar,
                      },
                  ]
                : [];
        });
        const administration = groups.value.find((group) => group.label === 'Verwaltung');
        return [
            { label: 'Registry', items: registry?.items ?? [] },
            { label: 'DICOM', items: dicom },
            {
                label: '',
                items: administration
                    ? [
                          {
                              label: administration.label,
                              href: administration.items[0].href,
                              paths: administration.items.flatMap((item) => item.paths),
                              icon: Settings,
                          },
                      ]
                    : [],
            },
        ].filter((section) => section.items.length > 0);
    });
    const activeGroup = computed(() =>
        groups.value.find((group) => group.label !== 'Registry' && group.items.some((item) => isActive(item.paths))),
    );
    return { primarySections, activeGroup, isActive };
}
