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
import { FormEvent, useEffect } from "react";
import { useSetPanelSize } from "@/hooks/use-set-panel-size";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Crops',
        href: '#',
    },
];

// Typical seeding rates per acre for common crops (in kg)
const SEEDING_RATES: { [key: string]: number } = {
    'rice': 16.19, // 16.19 kg per acre
    'corn': 8.09,  // 8.09 kg per acre
    'wheat': 50.58, // 50.58 kg per acre
    'barley': 50.58, // 50.58 kg per acre
    'soybean': 30.35, // 30.35 kg per acre
    'cotton': 6.07, // 6.07 kg per acre
    'sunflower': 3.24, // 3.24 kg per acre
    'tomato': 0.06, // 0.06 kg per acre
    'lettuce': 0.32, // 0.32 kg per acre
    'carrot': 1.21, // 1.21 kg per acre
    'cabbage': 0.16, // 0.16 kg per acre
    'onion': 1.62, // 1.62 kg per acre
    'potato': 728.52, // 728.52 kg (seed tubers) per acre
    'white potato': 728.52, // 728.52 kg per acre
    'bean': 32.37, // 32.37 kg per acre
    'pea': 48.56, // 48.56 kg per acre
    'pechay': 0.61, // 0.61 kg per acre
    'pechay baguio': 0.49, // 0.49 kg per acre
    'bell pepper': 0.12, // 0.12 kg per acre
    'eggplant': 0.08, // 0.08 kg per acre
    'ampalaya': 1.21, // 1.21 kg per acre
    'pole sitao': 20.23, // 20.23 kg per acre
    'squash': 1.62, // 1.62 kg per acre
    'broccoli': 0.16, // 0.16 kg per acre
    'cauliflower': 0.20, // 0.20 kg per acre
    'celery': 0.20, // 0.20 kg per acre
    'chayote': 0.81, // 0.81 kg per acre
    'habichuelas/baguio beans': 24.28, // 24.28 kg per acre
};

// Function to calculate seed weight (kg) based on area and crop type
const calculateSeedWeight = (acres: string, cropName: string): string => {
    const area = parseFloat(acres);
    if (!area || area <= 0 || !cropName) return '';

    const seedingRate = SEEDING_RATES[cropName.toLowerCase()] || 2.02; // Default rate
    const totalWeight = area * seedingRate;

    return totalWeight.toFixed(2);
};

const CropCreate = () => {
    useSetPanelSize(50);
    
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
        acres: '',
        seed_weight_kg: '',
        date_planted: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
        expected_harvest_date: '',
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

    // Automatically calculate seed weight when area or crop type changes
    useEffect(() => {
        if (data.acres && data.commodity_id) {
            const selectedCommodity = commodities.find(c => c.id.toString() === data.commodity_id);
            if (selectedCommodity) {
                const calculatedWeight = calculateSeedWeight(data.acres, selectedCommodity.name);
                if (calculatedWeight && calculatedWeight !== data.seed_weight_kg) {
                    setData('seed_weight_kg', calculatedWeight);
                }
            }
        }
    }, [data.acres, data.commodity_id, commodities, data.seed_weight_kg, setData]);

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
                                <Label htmlFor="acres" className="text-sm">Area (Acres) *</Label>
                                <Input
                                    id="acres"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="e.g., 6.2"
                                    value={data.acres}
                                    onChange={(e) => setData('acres', e.target.value)}
                                    className={`text-sm ${errors.acres ? 'border-destructive' : ''}`}
                                />
                                <InputError message={errors.acres} />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="seed_weight_kg" className="text-sm">
                                    Seed Weight (kg) *
                                    {data.acres && data.commodity_id && (
                                        <span className="text-xs text-muted-foreground ml-1">(Auto-calculated)</span>
                                    )}
                                </Label>
                                <Input
                                    id="seed_weight_kg"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="Enter area and crop type above"
                                    value={data.seed_weight_kg}
                                    onChange={(e) => setData('seed_weight_kg', e.target.value)}
                                    className={`text-sm ${errors.seed_weight_kg ? 'border-destructive' : ''} ${
                                        data.acres && data.commodity_id ? 'bg-muted/50' : ''
                                    }`}
                                />
                                {data.acres && data.commodity_id && (
                                    <p className="text-xs text-muted-foreground">
                                        Based on typical seeding rate for {commodities.find(c => c.id.toString() === data.commodity_id)?.name.toLowerCase()}
                                    </p>
                                )}
                                <InputError message={errors.seed_weight_kg} />
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