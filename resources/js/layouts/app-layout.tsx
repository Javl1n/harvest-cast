import AppMap from '@/components/map/app-map';
import { SidebarTrigger } from '@/components/ui/sidebar';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children?: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    hidden?: boolean
}

export default ({ children, breadcrumbs, hidden = false, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        <div className="h-full w-full">
            {!hidden && 
                <div className="absolute z-50 w-100 h-[calc(100%-(var(--spacing)*6))] mt-3 ms-3 overflow-auto bg-background/90 backdrop-blur-md rounded-lg">
                    {children}
                </div>
            }
            <div>
                <SidebarTrigger className='bg-sidebar absolute top-2 right-2 z-50' />
            </div>
            <AppMap />
        </div>
    </AppLayoutTemplate>
);
