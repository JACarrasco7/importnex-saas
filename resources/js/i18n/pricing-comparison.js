// Extension i18n para la tabla comparativa de precios (Sprint 5.1)
// Se fusiona con el diccionario principal en useTranslations.js via
// spread. Asi evitamos pisar es.js/en.js que otras sesiones pueden
// tener en working tree.

export const es = {
    pricing_compare: {
        compare_title: 'Compara los planes',
        compare_subtitle: 'Detalle completo de lo que incluye cada plan.',
        feature_label: 'Caracteristica',
        unlimited: 'Ilimitado',
        group_inventory: 'Inventario',
        group_crm: 'CRM y clientes',
        group_advanced: 'Funciones avanzadas',
        group_support: 'Soporte y formacion',
        feat: {
            cars: 'Vehiculos en catalogo',
            photos: 'Fotos por vehiculo',
            valuation: 'Paquete de valoracion PDF',
            kanban: 'Vista Kanban',
            clients: 'Clientes en CRM',
            contacts: 'Contactos',
            marketplace: 'Marketplace publico',
            whatsapp: 'Integracion WhatsApp',
            ai_verification: 'Verificacion con IA',
            map: 'Mapa interactivo',
            finance: 'Modulo Finanzas',
            trips: 'Modulo Viajes',
            webhooks: 'Webhooks salientes',
            support: 'Soporte',
            sla: 'SLA garantizado (24h)',
            training: 'Formacion personalizada',
        },
        support_email: 'Email',
        support_priority: 'Email prioritario',
        support_dedicated: 'Soporte dedicado 24/7',
    },
};

export const en = {
    pricing_compare: {
        compare_title: 'Compare plans',
        compare_subtitle: 'Full breakdown of what each plan includes.',
        feature_label: 'Feature',
        unlimited: 'Unlimited',
        group_inventory: 'Inventory',
        group_crm: 'CRM and clients',
        group_advanced: 'Advanced features',
        group_support: 'Support and training',
        feat: {
            cars: 'Vehicles in catalog',
            photos: 'Photos per vehicle',
            valuation: 'Valuation package PDF',
            kanban: 'Kanban view',
            clients: 'Clients in CRM',
            contacts: 'Contacts',
            marketplace: 'Public marketplace',
            whatsapp: 'WhatsApp integration',
            ai_verification: 'AI verification',
            map: 'Interactive map',
            finance: 'Finance module',
            trips: 'Trips module',
            webhooks: 'Outgoing webhooks',
            support: 'Support',
            sla: 'SLA guarantee (24h)',
            training: 'Personalized training',
        },
        support_email: 'Email',
        support_priority: 'Priority email',
        support_dedicated: 'Dedicated support 24/7',
    },
};
