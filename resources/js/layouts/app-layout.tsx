import { panelRefAtom, panelSizeAtom } from '@/atoms/panel-atom';
import AppMap from '@/components/map/app-map';
import { ResizableHandle, ResizablePanel, ResizablePanelGroup } from '@/components/ui/resizable';
import { SidebarTrigger } from '@/components/ui/sidebar';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { useAtom, useAtomValue } from 'jotai';
import { useEffect, useRef, type ReactNode } from 'react';

interface AppLayoutProps {
    children?: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    hidden?: boolean;
}

export default ({ children, breadcrumbs, hidden = false, ...props }: AppLayoutProps) => {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            <div className="h-full w-full">
                {!hidden && <ResizablePanelGroup direction='horizontal' className="w-full absolute pointer-events-none z-50 h-full p-3">
                    <ResizablePanel defaultSize={30} className="">
                        <div className="h-full w-full pointer-events-auto overflow-auto bg-background/90 backdrop-blur-md rounded-lg">
                            {children}
                        </div>
                    </ResizablePanel>
                    <ResizableHandle withHandle className='my-auto' />
                    <ResizablePanel />
                </ResizablePanelGroup>}
                <div>
                    <SidebarTrigger className='bg-sidebar absolute top-2 right-2 z-50' />
                </div>
                <AppMap />
            </div>
        </AppLayoutTemplate>
    );
}
