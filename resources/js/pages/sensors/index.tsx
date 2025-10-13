import { useSetPanelSize } from '@/hooks/use-set-panel-size';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import sensors from '@/routes/sensors';
import { SensorInterface, type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CircleDot } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
     {
          title: 'Sensors',
          href: sensors.index().url,
     },
];

const SensorsIndex = () => {
     const {sensors: sensorsData} = usePage<{sensors: SensorInterface[]}>().props;
     useSetPanelSize(30);
     return (
          <>
               <Head title="Sensors" />
               <div className='px-4 py-6'>
                    <div className="flex gap-4">
                         {/* <div className='flex gap-1 my-auto'>
                              <ChevronLeft/>
                              <ChevronRight/>
                         </div> */}
                         <div className='my-auto flex-1 text-2xl font-bold'>Sensors</div>
                    </div>
                    <div className='space-y-2 mt-2'>
                         {sensorsData.map((sensor) => (
                              <Link href={sensors.show(sensor)} key={sensor.id} className='flex rounded p-1 gap-2 hover:bg-accent transition'>
                                   <CircleDot className='h-10 w-10 my-auto' style={{
                                        color: `hsl(${(sensor.latest_reading.moisture / 100) * 120}, 100%, 50%)`
                                   }} />
                                   <div>
                                        <div className="font-bold text-lg">No Crop</div>
                                        <div className='font-light text-xs text-muted-foreground'>ID: {sensor.id}</div>
                                   </div>
                              </Link>
                         ))}
                    </div>
               </div>
              
          </>
     );
}

SensorsIndex.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default SensorsIndex;