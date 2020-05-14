<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Model\Company;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AttributeMetadataResolver;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Eav\Model\Config;

/**
 * Data provider for company.
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    const DATA_SCOPE_GENERAL = 'general';

    const DATA_SCOPE_INFORMATION = 'information';

    const DATA_SCOPE_ADDRESS = 'address';

    const DATA_SCOPE_COMPANY_ADMIN = 'company_admin';

    const DATA_SCOPE_SETTINGS = 'settings';

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * EAV attribute properties to fetch from meta storage.
     *
     * @var array
     */
    protected $metaProperties = [
        'dataType' => 'frontend_input',
        'visible' => 'is_visible',
        'required' => 'is_required',
        'label' => 'frontend_label',
        'sortOrder' => 'sort_order',
        'notice' => 'note',
        'default' => 'default_value',
        'size' => 'multiline_count',
    ];

    /**
     * Form element mapping.
     *
     * @var array
     */
    protected $formElement = [
        'text' => 'input',
        'hidden' => 'input',
        'boolean' => 'checkbox',
    ];

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * EAV attributes already exists in form definition
     *
     * @var array
     */
    private $skippedAttributeCodes = [
        'created_in' => true,
        'created_at' => true,
        'disable_auto_group_change' => true,
        'group_id' => true,
        CustomerInterface::WEBSITE_ID => true,
        CompanyInterface::EMAIL => true,
        CompanyInterface::PREFIX => true,
        CompanyInterface::FIRSTNAME => true,
        CompanyInterface::MIDDLENAME => true,
        CompanyInterface::LASTNAME => true,
        CompanyInterface::SUFFIX => true,
        CompanyInterface::GENDER => true
    ];

    /**
     * @var AttributeMetadataResolver
     */
    private $attributeMetadataResolver;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CompanyCollectionFactory $companyCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CustomerRepositoryInterface $customerRepository
     * @param Config $eavConfig
     * @param AttributeMetadataResolver $attributeMetadataResolver
     * @param array $meta [optional]
     * @param array $data [optional]
     * @throws \LogicException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CompanyCollectionFactory $companyCollectionFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CustomerRepositoryInterface $customerRepository,
        Config $eavConfig,
        AttributeMetadataResolver $attributeMetadataResolver,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $companyCollectionFactory->create();
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->customerRepository = $customerRepository;
        $this->eavConfig = $eavConfig;
        $this->attributeMetadataResolver = $attributeMetadataResolver;
    }

    /**
     * Get company general data.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return array
     */
    public function getGeneralData(CompanyInterface $company)
    {
        return [
            Company::NAME => $company->getCompanyName(),
            Company::STATUS => $company->getStatus(),
            Company::REJECT_REASON => $company->getRejectReason(),
            Company::REJECTED_AT => $company->getRejectedAt(),
            Company::COMPANY_EMAIL => $company->getCompanyEmail(),
            Company::SALES_REPRESENTATIVE_ID => $company->getSalesRepresentativeId(),
        ];
    }

    /**
     * Get company information data.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return array
     */
    public function getInformationData(CompanyInterface $company)
    {
        return [
            Company::LEGAL_NAME => $company->getLegalName(),
            Company::VAT_TAX_ID => $company->getVatTaxId(),
            Company::RESELLER_ID => $company->getResellerId(),
            Company::COMMENT => $company->getComment(),
        ];
    }

    /**
     * Get address data.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return array
     */
    public function getAddressData(CompanyInterface $company)
    {
        return [
            Company::STREET => $company->getStreet(),
            Company::CITY => $company->getCity(),
            Company::COUNTRY_ID => $company->getCountryId(),
            Company::REGION => $company->getRegion(),
            Company::REGION_ID => $company->getRegionId(),
            Company::POSTCODE => $company->getPostcode(),
            Company::TELEPHONE => $company->getTelephone(),
        ];
    }

    /**
     * Get company admin data.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCompanyAdminData(CompanyInterface $company)
    {
        $customer = $this->customerRepository->getById($company->getSuperUserId());
        $data = [
            Company::JOB_TITLE => $customer->getExtensionAttributes()->getCompanyAttributes()->getJobTitle(),
            Company::PREFIX => $customer->getPrefix(),
            Company::FIRSTNAME => $customer->getFirstname(),
            Company::MIDDLENAME => $customer->getMiddlename(),
            Company::LASTNAME => $customer->getLastname(),
            Company::SUFFIX => $customer->getSuffix(),
            Company::EMAIL => $customer->getEmail(),
            Company::GENDER => $customer->getGender(),
            CustomerInterface::WEBSITE_ID => $customer->getWebsiteId(),
        ];
        $customAttributes = $customer->getCustomAttributes();
        foreach ($customAttributes as $attribute) {
            if (!isset($this->skippedAttributeCodes[$attribute->getAttributeCode()])) {
                $data[$attribute->getAttributeCode()] = $attribute->getValue();
            }
        }
        return $data;
    }

    /**
     * Get settings data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function getSettingsData(CompanyInterface $company)
    {
        return [
            Company::CUSTOMER_GROUP_ID => $company->getCustomerGroupId(),
        ];
    }

    /**
     * Get data.
     *
     * @return array
     * @throws \LogicException
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $this->extensionAttributesJoinProcessor->process($this->collection);
        $items = $this->collection->getItems();
        $this->loadedData = [];
        /** @var Company $company */
        foreach ($items as $company) {
            $this->loadedData[$company->getId()] = $this->getCompanyResultData($company);
        }

        return $this->loadedData;
    }

    /**
     * Get company result data.
     *
     * @param CompanyInterface $company
     * @return array
     */
    public function getCompanyResultData(CompanyInterface $company)
    {
        $result = [
            self::DATA_SCOPE_GENERAL => $this->getGeneralData($company),
            self::DATA_SCOPE_INFORMATION => $this->getInformationData($company),
            self::DATA_SCOPE_ADDRESS => $this->getAddressData($company),
            self::DATA_SCOPE_COMPANY_ADMIN => $this->getCompanyAdminData($company),
            self::DATA_SCOPE_SETTINGS => $this->getSettingsData($company)
        ];
        $result['id'] = $company->getId();
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        $customerMeta = [];
        $entityType = $this->eavConfig->getEntityType('customer');
        $attributes = $entityType->getAttributeCollection();
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (!isset($this->skippedAttributeCodes[$attributeCode])) {
                $customerMeta[$attributeCode] = $this->attributeMetadataResolver->getAttributesMeta(
                    $attribute,
                    $entityType,
                    false
                );
            }
        }
        $this->attributeMetadataResolver->processWebsiteMeta($customerMeta);
        $meta['company_admin']['children'] = $customerMeta;

        return $meta;
    }
}