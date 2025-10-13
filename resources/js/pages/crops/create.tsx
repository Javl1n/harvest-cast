import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/input-error";
import CropRecommendations from "@/components/crop-recommendations";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem, CommodityInterface, SensorInterface, CropRecommendation, CurrentConditions } from "@/types";
import { Head, useForm, usePage } from "@inertiajs/react";
import { CalendarDays, Wheat } from "lucide-react";
import { FormEvent } from "react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Crops',
        href: '#',
    },
];

const CropCreate = () => {
    const { sensor, commodities, cropRecommendations, currentConditions, hasRecommendations } = usePage<{
        sensor: SensorInterface;
        commodities: CommodityInterface[];
        cropRecommendations: CropRecommendation[];
        currentConditions: CurrentConditions | null;
        hasRecommendations: boolean;
    }>().props;

    const { data, setData, post, processing, errors } = useForm({
        commodity_id: '',
        sensor_id: sensor.id,
        hectares: '',
        seeds_planted: '',
        date_planted: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
        expected_harvest_date: '',
        expected_income: '',
    });

    const handleRecommendationSelect = (crop: string, commodityId: number, harvestDays: number) => {
        setData(prevData => {
            // Calculate harvest date based on plant date and harvest days
            let expectedHarvestDate = '';
            if (prevData.date_planted) {
                const plantDate = new Date(prevData.date_planted);
                const harvestDate = new Date(plantDate);
                harvestDate.setDate(plantDate.getDate() + harvestDays);
                expectedHarvestDate = harvestDate.toISOString().split('T')[0]; // Format as YYYY-MM-DD
            }
            
            return {
                ...prevData,
                commodity_id: commodityId.toString(),
                expected_harvest_date: expectedHarvestDate
            };
        });
    };

    // Function to recalculate harvest date when plant date changes
    const handleDatePlantedChange = (newDatePlanted: string) => {
        setData(prevData => {
            const updatedData = { ...prevData, date_planted: newDatePlanted };
            
            // If a crop is selected and we have recommendations, recalculate harvest date
            if (newDatePlanted && prevData.commodity_id && hasRecommendations) {
                const selectedCommodity = commodities.find(c => c.id.toString() === prevData.commodity_id);
                const selectedRecommendation = cropRecommendations.find(
                    r => r.crop.toLowerCase() === selectedCommodity?.name.toLowerCase()
                );
                
                if (selectedRecommendation) {
                    const plantDate = new Date(newDatePlanted);
                    const harvestDate = new Date(plantDate);
                    harvestDate.setDate(plantDate.getDate() + selectedRecommendation.harvest_days);
                    updatedData.expected_harvest_date = harvestDate.toISOString().split('T')[0];
                }
            }
            
            return updatedData;
        });
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/crops');
    };

    return (
        <>
            <Head title={`Plant Crops - ${sensor.id}`} />
            <div className="px-4 py-6 space-y-4">
                <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <Wheat className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-foreground">Plant Crops</h1>
                        <p className="text-sm text-muted-foreground">Schedule a new crop planting for sensor {sensor.id}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-4">
                        <h2 className="text-sm font-medium text-muted-foreground flex items-center gap-2 uppercase tracking-wider">
                            <CalendarDays className="h-4 w-4" />
                            Crop Details
                        </h2>
                        
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="sensor_id" className="text-sm">Sensor ID</Label>
                                <Input 
                                    id="sensor_id"
                                    value={sensor.id} 
                                    disabled 
                                    className="bg-muted text-sm"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="commodity_id" className="text-sm">Crop Type *</Label>
                                <Select 
                                    value={data.commodity_id} 
                                    onValueChange={(value) => setData('commodity_id', value)}
                                >
                                    <SelectTrigger className={`text-sm ${errors.commodity_id ? 'border-destructive' : ''}`}>
                                        <SelectValue placeholder="Select a crop type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {commodities.map((commodity) => (
                                            <SelectItem key={commodity.id} value={commodity.id.toString()}>
                                                {commodity.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.commodity_id} />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="hectares" className="text-sm">Area (Hectares) *</Label>
                                <Input
                                    id="hectares"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="e.g., 2.5"
                                    value={data.hectares}
                                    onChange={(e) => setData('hectares', e.target.value)}
                                    className={`text-sm ${errors.hectares ? 'border-destructive' : ''}`}
                                />
                                <InputError message={errors.hectares} />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="seeds_planted" className="text-sm">Seeds Planted *</Label>
                                <Input
                                    id="seeds_planted"
                                    type="number"
                                    min="1"
                                    placeholder="e.g., 1000"
                                    value={data.seeds_planted}
                                    onChange={(e) => setData('seeds_planted', e.target.value)}
                                    className={`text-sm ${errors.seeds_planted ? 'border-destructive' : ''}`}
                                />
                                <InputError message={errors.seeds_planted} />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="date_planted" className="text-sm">Date Planted *</Label>
                                <Input
                                    id="date_planted"
                                    type="date"
                                    value={data.date_planted}
                                    onChange={(e) => handleDatePlantedChange(e.target.value)}
                                    className={`text-sm ${errors.date_planted ? 'border-destructive' : ''}`}
                                />
                                <InputError message={errors.date_planted} />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="expected_harvest_date" className="text-sm">Expected Harvest Date *</Label>
                                <Input
                                    id="expected_harvest_date"
                                    type="date"
                                    value={data.expected_harvest_date}
                                    onChange={(e) => setData('expected_harvest_date', e.target.value)}
                                    className={`text-sm ${errors.expected_harvest_date ? 'border-destructive' : ''}`}
                                />
                                <InputError message={errors.expected_harvest_date} />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="expected_income" className="text-sm">Expected Income (₱) *</Label>
                            <Input
                                id="expected_income"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="e.g., 50000.00"
                                value={data.expected_income}
                                onChange={(e) => setData('expected_income', e.target.value)}
                                className={`text-sm ${errors.expected_income ? 'border-destructive' : ''}`}
                            />
                            <InputError message={errors.expected_income} />
                        </div>
                    </div>

                    {hasRecommendations && (
                        <CropRecommendations
                            initialRecommendations={cropRecommendations}
                            currentConditions={currentConditions}
                            sensorId={sensor.id}
                            onRecommendationSelect={handleRecommendationSelect}
                            selectedCrop={commodities.find(c => c.id.toString() === data.commodity_id)?.name}
                            commodities={commodities}
                        />
                    )}

                    <div className="flex gap-2 mt-6">
                        <Button 
                            type="button" 
                            variant="outline"
                            onClick={() => window.history.back()}
                            disabled={processing}
                            className="flex-1"
                        >
                            Cancel
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={processing}
                            className="flex-1"
                        >
                            {processing ? 'Creating...' : 'Plant Crop'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
};

CropCreate.layout = (page: any) => <AppLayout children={page} breadcrumbs={breadcrumbs} />

export default CropCreate;