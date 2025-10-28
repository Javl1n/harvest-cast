import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import sensors from '@/routes/sensors';
import { CommodityInterface } from '@/types';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, CalendarDays } from 'lucide-react';

interface SensorCreateModalProps {
    isOpen: boolean;
    onClose: () => void;
    selectedDate?: Date;
    commodities?: CommodityInterface[];
}

const SensorCreateModal: React.FC<SensorCreateModalProps> = ({ 
    isOpen, 
    onClose, 
    selectedDate,
    commodities = []
}) => {
    const { data, setData, post, processing, errors, reset } = useForm({
        // Sensor data
        mac: '',
        
        // Planting/Schedule data
        commodity_id: '',
        hectares: '',
        seeds_planted: '',
        date_planted: selectedDate ? selectedDate.toISOString().split('T')[0] : '',
        expected_harvest_date: '',
        expected_income: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post('/sensors', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    const isValidSensorData = data.mac.length > 0;

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Calendar className="h-5 w-5" />
                        Add New Sensor & Planting
                    </DialogTitle>
                    <DialogDescription>
                        Register a new sensor and optionally add planting information.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit}>
                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label htmlFor="mac" className="text-right">
                                MAC Address
                            </Label>
                            <Input
                                id="mac"
                                placeholder="00:11:22:33:44:55"
                                value={data.mac}
                                onChange={(e) => setData('mac', e.target.value)}
                                className="col-span-3"
                            />
                        </div>
                        {errors.mac && (
                            <p className="text-sm text-destructive col-span-4">{errors.mac}</p>
                        )}

                        {/* Planting form fields */}
                        <div className="border-t pt-4 mt-4">
                            <h4 className="font-medium mb-4">Planting Information (Optional)</h4>
                            
                            <div className="grid gap-4">
                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="commodity" className="text-right">
                                        Crop
                                    </Label>
                                    <div className="col-span-3">
                                        <Select onValueChange={(value) => setData('commodity_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a crop" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {commodities.map((commodity) => (
                                                    <SelectItem key={commodity.id} value={commodity.id.toString()}>
                                                        {commodity.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="hectares" className="text-right">
                                        Hectares
                                    </Label>
                                    <Input
                                        id="hectares"
                                        type="number"
                                        step="0.01"
                                        placeholder="1.5"
                                        value={data.hectares}
                                        onChange={(e) => setData('hectares', e.target.value)}
                                        className="col-span-3"
                                    />
                                </div>

                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="seeds_planted" className="text-right">
                                        Seeds Planted
                                    </Label>
                                    <Input
                                        id="seeds_planted"
                                        type="number"
                                        placeholder="1000"
                                        value={data.seeds_planted}
                                        onChange={(e) => setData('seeds_planted', e.target.value)}
                                        className="col-span-3"
                                    />
                                </div>

                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="date_planted" className="text-right">
                                        Date Planted
                                    </Label>
                                    <Input
                                        id="date_planted"
                                        type="date"
                                        value={data.date_planted}
                                        onChange={(e) => setData('date_planted', e.target.value)}
                                        className="col-span-3"
                                    />
                                </div>

                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="expected_income" className="text-right">
                                        Expected Income
                                    </Label>
                                    <Input
                                        id="expected_income"
                                        type="number"
                                        step="0.01"
                                        placeholder="5000.00"
                                        value={data.expected_income}
                                        onChange={(e) => setData('expected_income', e.target.value)}
                                        className="col-span-3"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing || !isValidSensorData}>
                            {processing ? 'Creating...' : 'Create Sensor'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};

export default SensorCreateModal;