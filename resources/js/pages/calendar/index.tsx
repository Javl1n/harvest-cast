import CalendarPageController from '@/actions/App/Http/Controllers/CalendarPageController';
import { useSetPanelSize } from '@/hooks/use-set-panel-size';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { SensorInterface, type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Calendar',
        href: CalendarPageController.index().url,
    },
];

const CalendarIndex = () => {
     const {sensors} = usePage<{sensors: SensorInterface[]}>().props;
     
    return (
          <>
               <Head title="Calendar" />
               <div className='px-4 py-6'>
                    <div className="flex gap-4">
                         <div className='flex gap-1 my-auto'>
                              <ChevronLeft/>
                              <ChevronRight/>
                         </div>
                         <div className='my-auto flex-1 text-xl font-bold'>September 19, 2025</div>
                    </div>
                    <div className=''>
                         {sensors.map((sensor) => (
                              <div>
                                   <div>
                                        {sensor.id}
                                   </div>
                              </div>
                         ))}
                    </div>
               </div>
              
          </>
    );
}

CalendarIndex.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default CalendarIndex;