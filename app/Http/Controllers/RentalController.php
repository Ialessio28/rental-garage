<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "New Rental" for tenants. The page has 3 steps:
 *   1. choose vehicle type + garage type and the rental period (start date, daily/monthly, days)
 *   2. choose a garage that is free for the whole period, fill vehicle details, calculate the total
 *   3. upload the payment proof and save
 */
class RentalController extends Controller
{
    /** Form fields carried from step to step. */
    private const FIELDS = [
        'choice', 'start_date', 'rental_type', 'days', 'garage_id',
        'plate_number', 'brand_model', 'battery_capacity', 'current_charge',
    ];

    public function __construct(private RentalCalculator $calculator)
    {
    }

    /**
     * Show the rental page (all 3 steps, depending on what was filled in).
     */
    public function create(Request $request): View
    {
        $choices = $this->choices();
        $choice = $request->query('choice');
        $fields = $request->only(self::FIELDS);

        if (! isset($choices[$choice])) {
            return view('rentals.create', ['choices' => $choices, 'choice' => null, 'fields' => $fields]);
        }

        [$vehicleType, $garageType] = explode(':', $choice);
        $period = $this->validatePeriod($request);
        $garages = $this->availableGarages($garageType, $period);
        $summary = null;

        if ($request->has('calculate')) {
            $data = $this->validateRental($request, $vehicleType, $garageType, $garages, false);
            $summary = $this->calculate($data, $period, $garages->find($data['garage_id']), $vehicleType);
        }

        return view('rentals.create', [
            'choices' => $choices,
            'choice' => $choice,
            'vehicleType' => $vehicleType,
            'garageType' => $garageType,
            'period' => $period,
            'garages' => $garages,
            'summary' => $summary,
            'fields' => $fields,
        ]);
    }

    /**
     * Save the rental. The garage is "used" from the start date to the end date.
     */
    public function store(Request $request): RedirectResponse
    {
        $choice = $request->input('choice');
        abort_unless(isset($this->choices()[$choice]), 422);

        [$vehicleType, $garageType] = explode(':', $choice);
        $period = $this->validatePeriod($request);
        $garages = $this->availableGarages($garageType, $period);
        $data = $this->validateRental($request, $vehicleType, $garageType, $garages, true);
        $garage = $garages->find($data['garage_id']);
        $summary = $this->calculate($data, $period, $garage, $vehicleType);

        Rental::create([
            'user_id' => $request->user()->id,
            'garage_id' => $garage->id,
            'vehicle_type' => $vehicleType,
            'plate_number' => $vehicleType === 'bicycle' ? null : $data['plate_number'],
            'brand_model' => $data['brand_model'],
            'battery_capacity' => $vehicleType === 'electric_car' ? $data['battery_capacity'] : null,
            'current_charge' => $garageType === 'electric' ? $data['current_charge'] : null,
            'rental_type' => $period['rental_type'],
            'payment_proof' => $request->file('payment_proof')->store('proofs', 'public'),
        ] + $summary);

        return redirect()->route('rentals.create')->with('success',
            "Rental saved. Garage {$garage->code} is used from {$summary['start_date']->format('d M Y')} "
            . "until {$summary['end_date']->format('d M Y')}. "
            . 'Total paid: Rp ' . number_format($summary['total_cost'], 0, ',', '.'));
    }

    /**
     * Vehicle/garage pairs the tenant can choose, e.g. "electric_car:electric".
     *
     * @return array<string, string>  value => label
     */
    private function choices(): array
    {
        $choices = [];

        foreach (RentalCalculator::ALLOWED_GARAGES as $vehicleType => $garageTypes) {
            foreach ($garageTypes as $garageType) {
                $choices["$vehicleType:$garageType"] =
                    ucfirst(str_replace('_', ' ', $vehicleType)) . ' in ' . $garageType . ' garage';
            }
        }

        return $choices;
    }

    /**
     * Validate the rental period and work out its start, length and end.
     * The start date can be today or any later day.
     *
     * @param  Request  $request  the submitted form
     * @return array  rental_type, start (Carbon), days, end (Carbon)
     */
    private function validatePeriod(Request $request): array
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'rental_type' => ['required', Rule::in(['daily', 'monthly'])],
        ]);
        $start = Carbon::parse($data['start_date'])->startOfDay();

        // The daily maximum depends on the start month, so it is checked after the start date.
        $request->validate([
            'days' => ['exclude_if:rental_type,monthly', 'required', 'integer', 'min:1',
                'max:' . $this->calculator->maxDailyDays($start)],
        ]);
        $days = $this->calculator->rentalDays($start, $data['rental_type'], $request->integer('days'));

        return [
            'rental_type' => $data['rental_type'],
            'start' => $start,
            'days' => $days,
            'end' => $this->calculator->endDate($start, $days),
        ];
    }

    /**
     * Garages of the given type that are free for the whole rental period.
     *
     * @param  string  $garageType  bicycle | car | electric
     * @param  array   $period      from validatePeriod()
     * @return Collection  available garages ordered by code
     */
    private function availableGarages(string $garageType, array $period): Collection
    {
        return Garage::where('type', $garageType)
            ->availableBetween($period['start'], $period['end'])
            ->orderBy('code')
            ->get();
    }

    /**
     * Validate the garage and vehicle details. Rules depend on the vehicle and garage type.
     *
     * @param  Request     $request      the submitted form
     * @param  string      $vehicleType  bicycle | motorcycle | car | electric_car
     * @param  string      $garageType   bicycle | car | electric
     * @param  Collection  $garages      garages free for the period (the only valid choices)
     * @param  bool        $withProof    true on save, when the payment proof is required
     * @return array  the validated fields
     */
    private function validateRental(Request $request, string $vehicleType, string $garageType, Collection $garages, bool $withProof): array
    {
        $rules = [
            'garage_id' => ['required', Rule::in($garages->pluck('id'))],
            'plate_number' => [$vehicleType === 'bicycle' ? 'nullable' : 'required', 'string', 'max:15'],
            'brand_model' => ['required', 'string', 'max:100'],
            'battery_capacity' => [$vehicleType === 'electric_car' ? 'required' : 'nullable', 'numeric', 'gt:0'],
            'current_charge' => [$garageType === 'electric' ? 'required' : 'nullable', 'numeric', 'min:0', 'lte:battery_capacity'],
        ];

        if ($withProof) {
            $rules['payment_proof'] = ['required', 'image', 'max:' . Rental::MAX_PROOF_KB];
        }

        return $request->validate($rules, [
            'garage_id.in' => 'The chosen garage is not available for these dates.',
        ]);
    }

    /**
     * Work out all costs for the rental period.
     *
     * @param  array   $data         validated garage and vehicle fields
     * @param  array   $period       from validatePeriod()
     * @param  Garage  $garage       the chosen garage
     * @param  string  $vehicleType  bicycle | motorcycle | car | electric_car
     * @return array  days, start_date, end_date, rent_cost, charge_cost, total_cost
     */
    private function calculate(array $data, array $period, Garage $garage, string $vehicleType): array
    {
        $rentCost = $this->calculator->rentCost($garage->daily_price, $period['rental_type'], $period['days']);
        $chargeCost = $vehicleType === 'electric_car'
            ? $this->calculator->chargeCost($garage->type, (float) $data['battery_capacity'],
                (float) ($data['current_charge'] ?? 0), (int) $garage->kwh_price)
            : 0;

        return [
            'days' => $period['days'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'rent_cost' => $rentCost,
            'charge_cost' => $chargeCost,
            'total_cost' => $rentCost + $chargeCost,
        ];
    }
}
