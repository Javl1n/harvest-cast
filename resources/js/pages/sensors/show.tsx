import { useSetPanelSize } from "@/hooks/use-set-panel-size";
import AppLayout from "@/layouts/app-layout";
import crops from "@/routes/crops";
import sensors from "@/routes/sensors";
import { BreadcrumbItem, SensorInterface } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { Wheat } from "lucide-react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sensors',
        href: sensors.index().url,
    },
];

const SensorsShow = () => {
     const {sensor} = usePage<{sensor: SensorInterface}>().props;
     useSetPanelSize(30);

     return (
          <>
               <Head title={sensor.id} />
               <div className="px-4 py-6 space-y-2">
                    <div className="text-2xl font-bold">No Crops Planted</div>
                    <Link href={crops.create(sensor)}>
                         <div className="text-center p-4 border-3 border-dashed pointer-cursor hover:bg-muted">
                              <Wheat className="mx-auto" />
                              <div>Plant Crop</div>
                         </div>
                    </Link>
                    {/* <div className="text-sm">Sensor ID: {sensor.id}</div> */}
                    {/* <div className="text-sm">Current Location: {sensor.latest_reading.latitude}, {sensor.latest_reading.longitude}</div> */}
               </div>
          </>
     )
};

SensorsShow.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default SensorsShow;