import { useSetPanelSize } from '@/hooks/use-set-panel-size';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const Dashboard = () => {
    useSetPanelSize(0);
    return (
        <>
            <Head title="Dashboard" />
        </>
    );
}

Dashboard.layout = (page: any) => <AppLayout hidden children={page} breadcrumbs={breadcrumbs} />

export default Dashboard;