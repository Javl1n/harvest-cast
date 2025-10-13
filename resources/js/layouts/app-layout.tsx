import { panelRefAtom, panelSizeAtom, getSavedPanelSizeAtom, setPanelSizeAtom } from '@/atoms/panel-atom';
import AppMap from '@/components/map/app-map';
import WeatherWidget from '@/components/weather-widget';
import { ResizableHandle, ResizablePanel, ResizablePanelGroup } from '@/components/ui/resizable';
import { SidebarTrigger } from '@/components/ui/sidebar';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { SharedData, WeatherInterface, type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useAtom, useAtomValue, useSetAtom } from 'jotai';
import { useEffect, useRef, type ReactNode } from 'react';
import { ImperativePanelGroupHandle } from 'react-resizable-panels';

interface AppLayoutProps {
    children?: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    hidden?: boolean;
}

export default ({ children, breadcrumbs, hidden = false, ...props }: AppLayoutProps) => {
    const {weather} = usePage<SharedData>().props;
    const panelGroupRef = useRef<ImperativePanelGroupHandle>(null);
    const setPanelRef = useSetAtom(panelRefAtom);
    const [currentSize, setCurrentSize] = useAtom(panelSizeAtom);
    const savedSize = useAtomValue(getSavedPanelSizeAtom);

    // Set the panel ref when component mounts
    useEffect(() => {
        if (panelGroupRef.current) {
            setPanelRef(panelGroupRef.current);
            // Restore saved panel size on mount
            panelGroupRef.current.setLayout([savedSize, 100 - savedSize]);
            setCurrentSize(savedSize);
        }
    }, [setPanelRef, savedSize, setCurrentSize]);

    // Handle panel resize events to update the atom
    const handlePanelLayout = (sizes: number[]) => {
        const mainPanelSize = Math.round(sizes[0]);
        if (mainPanelSize !== currentSize) {
            setCurrentSize(mainPanelSize);
            // Persist to localStorage
            if (typeof window !== 'undefined') {
                localStorage.setItem('app-panel-size', mainPanelSize.toString());
            }
        }
    };

    console.log(weather);

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            <div className="h-full w-full">
                {true && <ResizablePanelGroup 
                    ref={panelGroupRef}
                    direction='horizontal' 
                    className="w-full absolute pointer-events-none z-50 h-full p-3"
                    onLayout={handlePanelLayout}
                >
                    <ResizablePanel defaultSize={savedSize} className="">
                        <div className="h-full w-full pointer-events-auto overflow-auto bg-background/90 backdrop-blur-md rounded-lg">
                            {children}
                        </div>
                    </ResizablePanel>
                    <ResizableHandle withHandle className='my-auto' />
                    <ResizablePanel />
                </ResizablePanelGroup>}
                <div className='flex gap-3 absolute top-4 right-4 z-50 pointer-events-none'>
                    <div className='pointer-events-auto'>
                        <WeatherWidget weather={weather} />
                    </div>
                    <SidebarTrigger className='bg-card/95 pointer-events-auto' />
                </div>
                <AppMap />
            </div>
        </AppLayoutTemplate>
    );
}
